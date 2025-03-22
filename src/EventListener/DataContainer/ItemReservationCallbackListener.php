<?php

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Contao\CoreBundle\Exception;

#[AsCallback(table: 'tl_dc_reservation_items', target: 'config.onsubmit')]
class ItemReservationCallbackListener
{
    private LoggerInterface $logger;
    private $db;

    public function __construct(Connection $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function __invoke(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }
        // Die gewählte Tabelle aus item_type bestimmen
        $itemType = $dc->activeRecord->item_type;           // Z. B. `tl_dc_tanks`, `tl_dc_regulators`, `tl_dc_equipment_types`

        $assetId = (int) $dc->activeRecord->item_id;        // Das ausgewählte Asset
        if($dc->activeRecord->reservation_status == 'returned' || $dc->activeRecord->reservation_status == 'canceled' ){
            $status = 'available';
        } else {
            $status = $dc->activeRecord->reservation_status;    // Neuer Status (z. B. aus Ihrer Reservierungslogik)
        }

        if (!$itemType || !$assetId) {
            return;
        }

        if($itemType == 'tl_dc_equipment_types'){
           $itemType = 'tl_dc_equipment_subtypes';
        }

        // Prüfen, ob es sich um eine unterstützte Tabelle handelt
        $allowedTables = [
            'tl_dc_tanks',
            'tl_dc_regulators',
            'tl_dc_equipment_subtypes',
        ];

        if (!in_array($itemType, $allowedTables, true)) {
            // Falls die Tabelle nicht erlaubt ist, nichts tun
            $this->logger->error('Ungültige Tabelle: $itemType für Asset ID $assetId', [__METHOD__, TL_ERROR]);
            return;
        }

        // Status des entsprechenden Assets in der richtigen Tabelle aktualisieren
            $this->db->update(
                $itemType,                  // Tabelle aus item_type
                ['status' => $status],      // Zu setzende Spalten
                ['id'  => $assetId]        // Bedingung (ID des Assets)
            );
    }
}
