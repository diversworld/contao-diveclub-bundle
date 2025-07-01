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

    public function __invoke(array $row, string $label, DataContainer $dc): string
    {
        // Fallback-Werte definieren
        $typeLabel = $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes'][$row['item_type']] ?? 'Unbekannter Typ';
        $reservedAt = !empty($row['reserved_at']) ? date($GLOBALS['TL_CONFIG']['datimFormat'], (int)$row['reserved_at']) : 'Unbekannt';
        $createdAt = !empty($row['created_at']) ? date($GLOBALS['TL_CONFIG']['datimFormat'], (int)$row['created_at']) : 'Unbekannt';

        $sizes = $this->helper->getSizes();

        // Daten basierend auf dem Typ laden
        switch ($row['item_type']) {
            case 'tl_dc_tanks': // Tanks
                $dbResult = DcTanksModel::findById((int)$row['item_id']);
                if ($dbResult) {
                    $result = $dbResult->row();
                    $row['asset_type'] = $typeLabel;
                    $row['asset_id'] = sprintf('%sL - %s, (Miete: %s €)',
                        $result['size'] ?? '-',
                        $result['title'] ?? 'Kein Titel',
                        number_format((float)$result['rentalFee'], 2, ',', '.')
                    );
                } else {
                    $row['asset_type'] = $typeLabel;
                    $row['asset_id'] = 'Tank nicht gefunden';
                }
                break;

            case 'tl_dc_regulators': // Regulatoren
                $dbResult = DcRegulatorsModel::findById((int)$row['item_id']);
                if ($dbResult) {
                    $result = $dbResult->row();
                    $manufacturerName = $this->helper->getManufacturers()[$result['manufacturer']] ?? 'Unbekannter Hersteller';
                    $row['asset_type'] = $typeLabel;
                    $row['asset_id'] = sprintf('%s - %s, 1.Stufe: %s, 2.Stufe Pri.: %s, 2.Stufe Sec.: %s, (Miete: %s €)',
                        $result['title'] ?? 'Kein Titel',
                        $manufacturerName,
                        $result['regModel1st'] ?? '-',
                        $result['regModel2ndPri'] ?? '-',
                        $result['regModel2ndSec'] ?? '-',
                        number_format((float)$result['rentalFee'], 2, ',', '.')
                    );
                } else {
                    $row['asset_type'] = $typeLabel;
                    $row['asset_id'] = 'Regulator nicht gefunden';
                }
                break;

            case 'tl_dc_equipment': // Equipment-Typen
                $dbResult = DcEquipmentModel::findById((int)$row['item_id']);
                if ($dbResult) {
                    $result = $dbResult->row();
                    $row['asset_type'] = $typeLabel;
                    $row['asset_id'] = sprintf('%s, %s - %s, (Miete: %s €)',
                        $result['model'] ?? 'Kein Modell',
                        $sizes[$result['size']] ?? '-',
                        $result['title'] ?? 'Kein Titel',
                        number_format((float)$result['rentalFee'], 2, ',', '.')
                    );
                } else {
                    $row['asset_type'] = $typeLabel;
                    $row['asset_id'] = 'Equipment nicht gefunden';
                }
                break;
        }

        // Format müssen wir entsprechend den Anforderungen anpassen
        return sprintf(
            '%s, %s - %s - %s - %s',
            $row['asset_type'],    // Typ des Assets
            $row['asset_id'],      // ID bzw. Titel des Assets
            $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$row['reservation_status']], // Status der Reservierung
            $reservedAt,           // Zeitpunkt der Reservierung
            $createdAt,            // Zeitpunkt der Erstellung
        );

    }
}
