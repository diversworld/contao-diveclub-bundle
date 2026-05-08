<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

class ProposalListener
{
    use AliasHandlerTrait;

    public function __construct(
        private readonly Connection      $db,
        private readonly ContaoFramework $framework,
        private readonly LoggerInterface $logger,
        private readonly RouterInterface $router,
        private readonly Slug            $slug,
    )
    {
    }

    #[AsCallback(table: 'tl_dc_check_proposal', target: 'fields.checkId.options')]
    #[AsCallback(table: 'tl_dc_check_articles', target: 'fields.pid.options')]
    #[AsCallback(table: 'tl_dc_check_booking', target: 'fields.pid.options')]
    #[AsCallback(table: 'tl_dc_regulator_control', target: 'fields.pid.options')]
    public function getCheckIdOptions(): array
    {
        $options = [];
        $result = $this->db->fetchAllAssociative("SELECT id, title FROM tl_dc_check_proposal ORDER BY title");

        foreach ($result as $row) {
            $options[$row['id']] = $row['title'];
        }

        return $options;
    }

    #[AsCallback(table: 'tl_dc_check_proposal', target: 'fields.eventId.options')]
    #[AsCallback(table: 'tl_dc_dive_course', target: 'fields.eventId.options')]
    public function getEventOptions(): array
    {
        $options = [];
        $result = $this->db->fetchAllAssociative("SELECT id, title FROM tl_calendar_events ORDER BY title");

        foreach ($result as $row) {
            $options[$row['id']] = $row['title'];
        }

        return $options;
    }

    #[AsCallback(table: 'tl_dc_check_proposal', target: 'fields.alias.save')]
    public function generateAlias(mixed $varValue, DataContainer $dc): mixed
    {
        return $this->generateAliasWithValidation($this->db, $this->slug, $varValue, $dc, 'tl_dc_check_proposal');
    }

    #[AsCallback(table: 'tl_dc_check_proposal', target: 'fields.checkId.save')]
    public function updateEventVendorInfo(mixed $varValue, DataContainer $dc): mixed
    {
        if (!empty($varValue) && is_numeric($varValue)) {
            $eventExists = $this->db->fetchOne("SELECT id FROM tl_calendar_events WHERE id = ? LIMIT 1", [$varValue]);

            if ($eventExists) {
                $vendor = (int)$dc->activeRecord->id;
                if ($vendor > 0) {
                    $this->db->executeStatement(
                        "UPDATE tl_calendar_events SET addVendorInfo = ? WHERE id = ?",
                        [$vendor, $varValue]
                    );

                    $this->logger->info(
                        'Vendor-Info für Event-ID ' . $varValue . ' aktualisiert: ' . $vendor,
                        ['contao' => new ContaoContext(__METHOD__, ContaoContext::GENERAL)]
                    );
                }
            } else {
                throw new \RuntimeException(sprintf('Das Event mit der ID %d existiert nicht.', $varValue));
            }
        }

        return $varValue;
    }

    #[AsCallback(table: 'tl_dc_check_proposal', target: 'list.operations.tuv_list.button')]
    public function generateTuvListButton(array $row, ?string $href, ?string $label, ?string $title, ?string $icon, ?string $attributes): string
    {
        $url = $this->router->generate('dc_tuv_list_export', ['id' => $row['id']]);

        // Always use the language strings to ensure correct label and title
        $labelValue = $GLOBALS['TL_LANG']['tl_dc_check_proposal']['tuv_list'][0] ?? 'TÜV-Liste';
        $titleValue = $GLOBALS['TL_LANG']['tl_dc_check_proposal']['tuv_list'][1] ?? 'TÜV-Liste';

        if ($icon === null) {
            $icon = 'bundles/diversworldcontaodiveclub/icons/pdf.svg';
        }

        return '<a href="' . $url . '" title="' . StringUtil::specialchars($labelValue) . '" ' . ($attributes ?? '') . ' target="_blank">' . Image::getHtml($icon, $titleValue) . '</a> ';
    }
}
