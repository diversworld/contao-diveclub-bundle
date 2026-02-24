<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Diversworld\ContaoDiveclubBundle\Model\DcEquipmentModel;
use Diversworld\ContaoDiveclubBundle\Model\DcRegulatorsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcTanksModel;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_dc_reservation_items', target: 'list.label.label')]
class ReservationItemsLabelCallback
{
    private Connection $db;
    private DcaTemplateHelper $helper;

    public function __construct(Connection $db, DcaTemplateHelper $helper)
    {
        $this->db = $db;
        $this->helper = $helper;
    }

    public function __invoke(array $row, string $label, DataContainer $dc, ?array $args = null): array|string
    {
        // Fallback-Werte definieren
        $typeLabel = $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes'][$row['item_type']] ?? 'Unbekannter Typ';
        $reservedAt = !empty($row['reserved_at']) ? date($GLOBALS['TL_CONFIG']['datimFormat'], (int)$row['reserved_at']) : 'Unbekannt';
        $createdAt = !empty($row['created_at']) ? date($GLOBALS['TL_CONFIG']['datimFormat'], (int)$row['created_at']) : 'Unbekannt';
        $updatedAt = !empty($row['updated_at']) ? date($GLOBALS['TL_CONFIG']['datimFormat'], (int)$row['updated_at']) : 'Unbekannt';

        $sizes = $this->helper->getSizes();
        $assetIdLabel = $row['item_id'];

        // Daten basierend auf dem Typ laden
        switch ($row['item_type']) {
            case 'tl_dc_tanks': // Tanks
                $dbResult = DcTanksModel::findById((int)$row['item_id']);
                if ($dbResult) {
                    $result = $dbResult->row();
                    $assetIdLabel = sprintf('%sL - %s, (Miete: %s €)',
                        $result['size'] ?? '-',
                        $result['title'] ?? 'Kein Titel',
                        number_format((float)$result['rentalFee'], 2, ',', '.')
                    );
                } else {
                    $assetIdLabel = 'Tank nicht gefunden';
                }
                break;

            case 'tl_dc_regulators': // Regulatoren
                $dbResult = DcRegulatorsModel::findById((int)$row['item_id']);
                if ($dbResult) {
                    $result = $dbResult->row();
                    $manufacturerName = $this->helper->getManufacturers()[$result['manufacturer']] ?? 'Unbekannter Hersteller';
                    $assetIdLabel = sprintf('%s - %s, 1.Stufe: %s, 2.Stufe Pri.: %s, 2.Stufe Sec.: %s, (Miete: %s €)',
                        $result['title'] ?? 'Kein Titel',
                        $manufacturerName,
                        $result['regModel1st'] ?? '-',
                        $result['regModel2ndPri'] ?? '-',
                        $result['regModel2ndSec'] ?? '-',
                        number_format((float)$result['rentalFee'], 2, ',', '.')
                    );
                } else {
                    $assetIdLabel = 'Regulator nicht gefunden';
                }
                break;

            case 'tl_dc_equipment': // Equipment-Typen
                $dbResult = DcEquipmentModel::findById((int)$row['item_id']);
                if ($dbResult) {
                    $result = $dbResult->row();
                    $assetIdLabel = sprintf('%s, %s - %s, (Miete: %s €)',
                        $result['model'] ?? 'Kein Modell',
                        $sizes[$result['size']] ?? '-',
                        $result['title'] ?? 'Kein Titel',
                        number_format((float)$result['rentalFee'], 2, ',', '.')
                    );
                } else {
                    $assetIdLabel = 'Equipment nicht gefunden';
                }
                break;
        }

        $statusLabel = $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$row['reservation_status']] ?? $row['reservation_status'];

        if (null !== $args) {
            $labels = $args;
            $labels[0] = $typeLabel;
            $labels[1] = $assetIdLabel;
            $labels[2] = $row['types'] ? ($this->helper->getEquipmentFlatTypes()[$row['types']] ?? $row['types']) : '-';
            $labels[3] = $row['sub_type'] ?: '-';
            $labels[4] = $statusLabel;
            $labels[5] = $createdAt;
            $labels[6] = $updatedAt;

            return $labels;
        }

        // Format für Listenansicht (ohne Spalten)
        return sprintf(
            '%s, %s - %s - %s - %s',
            $typeLabel,
            $assetIdLabel,
            $statusLabel,
            $reservedAt,
            $createdAt
        );
    }
}
