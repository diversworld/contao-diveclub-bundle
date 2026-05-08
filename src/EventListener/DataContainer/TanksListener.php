<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Contao\System;
use DateTime;
use Doctrine\DBAL\Connection;
use Exception;
use Psr\Log\LoggerInterface;

class TanksListener
{
    use AliasHandlerTrait;

    public function __construct(
        private readonly Connection      $db,
        private readonly LoggerInterface $logger,
        private readonly Slug            $slug
    )
    {
    }

    #[AsCallback(table: 'tl_dc_tanks', target: 'fields.alias.save')]
    public function onAliasSave(mixed $varValue, DataContainer $dc): mixed
    {
        return $this->generateAliasWithValidation($this->db, $this->slug, $varValue, $dc, 'tl_dc_tanks');
    }

    #[AsCallback(table: 'tl_dc_tanks', target: 'fields.checkId.options')]
    public function onCheckIdOptions(): array
    {
        $events = $this->db->fetchAllAssociative("SELECT id, title FROM tl_calendar_events WHERE addCheckInfo = '1' and published = '1'");
        $options = [];

        foreach ($events as $event) {
            $options[$event['id']] = $event['title'];
        }

        return $options;
    }

    #[AsCallback(table: 'tl_dc_tanks', target: 'fields.checkId.save')]
    public function onCheckIdSave($varValue, DataContainer $dc)
    {
        if ($varValue) {
            $startDate = $this->db->fetchOne("SELECT startDate FROM tl_calendar_events WHERE id = ?", [$varValue]);

            if ($startDate) {
                $lastCheckDate = new DateTime('@' . $startDate);
                $lastCheckDate->modify('+2 years');
                $nextCheckDate = $lastCheckDate->getTimestamp();

                $this->db->executeStatement(
                    "UPDATE tl_dc_tanks SET lastCheckDate = ?, nextCheckDate = ? WHERE id = ?",
                    [$startDate, $nextCheckDate, $dc->id]
                );
            }
        }

        return $varValue;
    }

    #[AsCallback(table: 'tl_dc_tanks', target: 'list.label.label')]
    public function onLabelCallback(array $row, string $label, ?DataContainer $dc = null, ?array $args = null): string
    {
        $owners = $this->getOwnerOptions();
        $ownerName = $owners[$row['owner']] ?? 'N/A';

        $title = $row['title'] ?? '';
        $serialnumber = $row['serialNumber'] ?? '';
        $size = $row['size'] ?? '';
        $manufacturer = $row['manufacturer'] ?? '';
        $o2CleanValue = ($row['o2clean'] == 1) ? 'ja' : 'nein';

        $lastCheckDate = isset($row['lastCheckDate']) && is_numeric($row['lastCheckDate'])
            ? date('d.m.Y', (int)$row['lastCheckDate'])
            : 'N/A';

        $nextCheckDate = isset($row['nextCheckDate']) && is_numeric($row['nextCheckDate'])
            ? date('d.m.Y', (int)$row['nextCheckDate'])
            : 'N/A';

        return sprintf('%s - %s - %s L - %s - O2: %s - %s - letzter TÜV %s - nächster TÜV %s',
            $title,
            $serialnumber,
            $size,
            $manufacturer,
            $o2CleanValue,
            $ownerName,
            $lastCheckDate,
            $nextCheckDate
        );
    }

    #[AsCallback(table: 'tl_dc_tanks', target: 'fields.rentalFee.save')]
    public function onRentalFeeSave($value): float
    {
        if (empty($value)) {
            return 0.00;
        }

        $value = str_replace(['€', ' '], '', (string)$value);
        return round((float)$value, 2);
    }

    #[AsCallback(table: 'tl_dc_check_order', target: 'fields.tankId.options')]
    public function onOrderTankIdOptions(DataContainer $dc): array
    {
        $options = [];

        if (!$dc->activeRecord) {
            return $options;
        }

        $memberId = (int)$this->db->fetchOne("SELECT memberId FROM tl_dc_check_booking WHERE id=?", [$dc->activeRecord->pid]);

        // 1. Privatflaschen des Mitglieds
        if ($memberId) {
            $rows = $this->db->fetchAllAssociative("SELECT id, title, serialNumber FROM tl_dc_tanks WHERE owner=? ORDER BY title", [$memberId]);
            foreach ($rows as $row) {
                $options['Mitgliedsflaschen'][$row['id']] = sprintf('%s (SN: %s)', $row['title'], $row['serialNumber']);
            }
        }

        // 2. Vereinsflaschen (owner = 0 oder ein spezielles Admin-Mitglied, hier nehmen wir owner = 0 als Verein)
        $clubTanks = $this->db->fetchAllAssociative("SELECT id, title, serialNumber FROM tl_dc_tanks WHERE owner=0 OR owner IS NULL ORDER BY title");
        foreach ($clubTanks as $row) {
            $options['Vereinsflaschen'][$row['id']] = sprintf('%s (SN: %s)', $row['title'], $row['serialNumber']);
        }

        return $options;
    }

    #[AsCallback(table: 'tl_dc_tanks', target: 'fields.owner.options')]
    public function onOwnerOptionsCallback(): array
    {
        return $this->getOwnerOptions();
    }

    private function getOwnerOptions(): array
    {
        $owners = $this->db->fetchAllAssociative("SELECT id, CONCAT(firstname, ' ', lastname) as name FROM tl_member ORDER BY lastname, firstname");
        $options = [];

        foreach ($owners as $owner) {
            $options[$owner['id']] = $owner['name'];
        }

        return $options;
    }
}
