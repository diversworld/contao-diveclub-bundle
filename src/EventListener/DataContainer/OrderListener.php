<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;

class OrderListener
{
    public function __construct(private readonly Connection $db)
    {
    }

    #[AsCallback(table: 'tl_dc_check_order', target: 'list.label.label')]
    public function onLabelCallback(array $row, string $label, DataContainer $dc, ?array $args = null): array|string
    {
        $sizeLabel = $GLOBALS['TL_LANG']['tl_dc_check_order']['sizes'][$row['size']] ?? $row['size'];
        $statusLabel = $GLOBALS['TL_LANG']['tl_dc_check_order']['status_reference'][$row['status']] ?? $row['status'];

        if (null === $args) {
            return sprintf(
                '%s (%s) - %s € [%s]',
                $row['serialNumber'],
                $sizeLabel,
                number_format((float)$row['totalPrice'], 2, ',', '.'),
                $statusLabel
            );
        }

        $args[1] = number_format((float)$row['totalPrice'], 2, ',', '.') . ' €';
        $args[2] = $statusLabel;
        $args[0] = sprintf(
            '%s (%s) - %s -%s€',
            $row['serialNumber'],
            $sizeLabel,
            $args[2],
            $args[1]
        );

        return $args;
    }

    #[AsCallback(table: 'tl_dc_check_order', target: 'config.onload')]
    public function onLoadCallback(DataContainer $dc): void
    {
        if (!$dc->id || 'edit' !== Input::get('act')) {
            return;
        }

        $rowOrder = $this->db->fetchAssociative("SELECT * FROM tl_dc_check_order WHERE id=?", [$dc->id]);
        if (!$rowOrder) {
            return;
        }

        $hasChanges = $this->handleTankData($dc, $rowOrder);
        $hasChanges = $this->handleSizeArticleAutoSelection($dc, $rowOrder) || $hasChanges;

        if ($hasChanges) {
            Controller::reload();
        }
    }

    #[AsCallback(table: 'tl_dc_check_order', target: 'config.oncreate')]
    public function onCreateCallback(string $table, int $insertId, array $set, DataContainer $dc): void
    {
        if ('tl_dc_check_order' !== $table) {
            return;
        }

        $pid = $this->db->fetchOne("SELECT pid FROM tl_dc_check_order WHERE id=?", [$insertId]);
        $pid = $pid ?: ($set['pid'] ?? 0);

        if (!$pid) {
            return;
        }

        $bookingNumber = $this->db->fetchOne("SELECT bookingNumber FROM tl_dc_check_booking WHERE id=?", [$pid]);

        if ($bookingNumber) {
            $this->db->update('tl_dc_check_order', ['bookingId' => $bookingNumber], ['id' => $insertId]);
        }
    }

    #[AsCallback(table: 'tl_dc_check_order', target: 'config.onsubmit')]
    public function onSubmitCallback(DataContainer $dc): void
    {
        if (!$dc->activeRecord) {
            $rowOrder = $this->db->fetchAssociative("SELECT * FROM tl_dc_check_order WHERE id=?", [$dc->id]);
            if (!$rowOrder) {
                return;
            }
            $dc->activeRecord = (object)$rowOrder;
        }

        // Handle price calculation
        $this->updatePrice($dc);

        // Handle tank status update if 'pickedup'
        if ($dc->activeRecord->status === 'pickedup') {
            $this->updateTankStatus($dc->activeRecord);
        }
    }

    #[AsCallback(table: 'tl_dc_check_order', target: 'config.ondelete')]
    public function onDeleteCallback(DataContainer $dc): void
    {
        if (!$dc->activeRecord) {
            return;
        }

        $this->updateBookingPrice((int)$dc->activeRecord->pid);
    }

    #[AsCallback(table: 'tl_dc_check_order', target: 'fields.selectedArticles.options')]
    public function onSelectedArticlesOptions(DataContainer $dc): array
    {
        $options = [];
        if (!$dc->activeRecord) {
            return $options;
        }

        $proposalId = (int)$this->db->fetchOne("SELECT pid FROM tl_dc_check_booking WHERE id=?", [$dc->activeRecord->pid]);
        if (!$proposalId) {
            return $options;
        }

        $rows = $this->db->fetchAllAssociative("SELECT id, title, articlePriceBrutto FROM tl_dc_check_articles WHERE pid=?", [$proposalId]);
        foreach ($rows as $row) {
            $options[$row['id']] = $row['title'] . ' (' . number_format((float)$row['articlePriceBrutto'], 2, ',', '.') . ' €)';
        }

        return $options;
    }

    #[AsCallback(table: 'tl_dc_check_order', target: 'fields.size.options')]
    public function onSizeOptions(DataContainer $dc): array
    {
        System::loadLanguageFile('tl_dc_check_order');
        $sizes = $GLOBALS['TL_LANG']['tl_dc_check_order']['sizes'] ?? null;
        if (\is_array($sizes)) {
            return array_keys($sizes);
        }

        return ['1', '2', '3', '4', '5', '6', '7', '8', '10', '12', '15', '18', '20', '11', '22'];
    }

    private function handleTankData(DataContainer $dc, array $rowOrder): bool
    {
        $tankId = (int)(Input::post('tankId') ?? $rowOrder['tankId']);

        if ($tankId > 0) {
            $rowTank = $this->db->fetchAssociative("SELECT * FROM tl_dc_tanks WHERE id=?", [$tankId]);
            if ($rowTank) {
                $set = [];
                $isNewTank = ($tankId !== (int)$rowOrder['tankId']);

                if ($isNewTank || !$rowOrder['serialNumber']) {
                    $set['serialNumber'] = $rowTank['serialNumber'];
                }
                if ($isNewTank || !$rowOrder['manufacturer']) {
                    $set['manufacturer'] = $rowTank['manufacturer'];
                }
                if ($isNewTank || ($rowTank['bazNumber'] !== $rowOrder['bazNumber'])) {
                    $set['bazNumber'] = $rowTank['bazNumber'] ?: '';
                }
                if ($isNewTank || !$rowOrder['size']) {
                    $set['size'] = $rowTank['size'];
                }
                if ($isNewTank || (bool)$rowTank['o2clean'] !== (bool)$rowOrder['o2clean']) {
                    $set['o2clean'] = $rowTank['o2clean'] ? '1' : '';
                }

                if (!empty($set)) {
                    $set['tankId'] = $tankId;
                    $this->db->update('tl_dc_check_order', $set, ['id' => $dc->id]);
                    return true;
                }
            }
        }
        return false;
    }

    private function handleSizeArticleAutoSelection(DataContainer $dc, array $rowOrder): bool
    {
        $size = Input::post('size') ?? $rowOrder['size'];
        if (!$size) {
            return false;
        }

        $proposalId = (int)$this->db->fetchOne("SELECT pid FROM tl_dc_check_booking WHERE id=?", [$rowOrder['pid']]);
        if (!$proposalId) {
            return false;
        }

        $articles = $this->db->fetchAllAssociative("SELECT id, articleSize, `default` FROM tl_dc_check_articles WHERE pid=?", [$proposalId]);

        $articleIdsToSelect = [];
        $sizeArticleIds = [];
        $bestMatchingArticleId = null;
        $minMatchingSize = 999999.0;
        $targetSize = (float)str_replace(',', '.', $size);

        foreach ($articles as $article) {
            if ($article['articleSize'] !== '') {
                $currentArticleSize = (float)str_replace(',', '.', $article['articleSize']);
                $sizeArticleIds[] = (int)$article['id'];
                if ($currentArticleSize >= $targetSize && $currentArticleSize < $minMatchingSize) {
                    $minMatchingSize = $currentArticleSize;
                    $bestMatchingArticleId = (int)$article['id'];
                }
            }
            if ($article['default']) {
                $articleIdsToSelect[] = (int)$article['id'];
            }
        }

        if ($bestMatchingArticleId) {
            $articleIdsToSelect[] = $bestMatchingArticleId;
        }

        $selectedArticles = StringUtil::deserialize($rowOrder['selectedArticles'], true);
        $newSelected = array_diff($selectedArticles, $sizeArticleIds);
        foreach ($articleIdsToSelect as $id) {
            if (!\in_array($id, $newSelected, true)) {
                $newSelected[] = $id;
            }
        }
        sort($newSelected);

        $oldSelected = $selectedArticles;
        sort($oldSelected);

        $hasChanges = false;
        if (Input::post('size') !== null && Input::post('size') !== $rowOrder['size']) {
            $this->db->update('tl_dc_check_order', ['size' => Input::post('size')], ['id' => $dc->id]);
            $hasChanges = true;
        }

        if ($newSelected !== $oldSelected) {
            $this->db->update('tl_dc_check_order', ['selectedArticles' => serialize($newSelected)], ['id' => $dc->id]);
            $hasChanges = true;
        }

        if ($hasChanges) {
            $dc->activeRecord = (object)$this->db->fetchAssociative("SELECT * FROM tl_dc_check_order WHERE id=?", [$dc->id]);
            $this->updatePrice($dc);
        }

        return $hasChanges;
    }

    private function updatePrice(DataContainer $dc): void
    {
        $selected = StringUtil::deserialize($dc->activeRecord->selectedArticles, true);
        $totalPrice = 0.0;
        if (!empty($selected)) {
            $totalPrice = (float)$this->db->fetchOne(
                "SELECT SUM(articlePriceBrutto) FROM tl_dc_check_articles WHERE id IN (" . implode(',', array_map('intval', $selected)) . ")"
            );
        }

        $this->db->update('tl_dc_check_order', ['totalPrice' => $totalPrice], ['id' => $dc->id]);
        $dc->activeRecord->totalPrice = $totalPrice;

        $this->updateBookingPrice((int)$dc->activeRecord->pid);
    }

    private function updateBookingPrice(int $bookingId): void
    {
        $totalPrice = (float)$this->db->fetchOne("SELECT SUM(totalPrice) FROM tl_dc_check_order WHERE pid=?", [$bookingId]);
        $this->db->update('tl_dc_check_booking', ['totalPrice' => $totalPrice], ['id' => $bookingId]);
    }

    private function updateTankStatus($activeRecord): void
    {
        $serialNumber = $activeRecord->serialNumber;
        if (!$serialNumber) return;

        $tankId = $this->db->fetchOne('SELECT id FROM tl_dc_tanks WHERE serialNumber = ?', [$serialNumber]);
        if (!$tankId) return;

        $today = new \DateTime();
        $nextCheck = (clone $today)->modify('+2 years');

        $this->db->update('tl_dc_tanks', [
            'lastCheckDate' => $today->getTimestamp(),
            'nextCheckDate' => $nextCheck->getTimestamp(),
            'lastOrder' => (string)($activeRecord->bookingId ?: $activeRecord->pid)
        ], ['id' => $tankId]);
    }
}
