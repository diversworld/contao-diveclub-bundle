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

        // Auslesen der relevanten Felder
        $pickedUpAt = $dc->activeRecord->picked_up_at;
        $returnedAt = $dc->activeRecord->returned_at;
        $reservationStatus = $dc->activeRecord->reservation_status;
        $itemType = $dc->activeRecord->item_type;           // Z. B. `tl_dc_tanks`, `tl_dc_regulators`, `tl_dc_equipment`
        $assetId = (int) $dc->activeRecord->item_id;        // Das ausgewählte Asset

        if($dc->activeRecord->reservation_status == 'returned' || $dc->activeRecord->reservation_status == 'cancelled' ){
            $status = 'available';
        } else {
            $status = $dc->activeRecord->reservation_status;    // Neuer Status (z. B. aus Ihrer Reservierungslogik)
        }

        if (!$itemType || !$assetId) {
            return;
		}

        // Prüfen, ob es sich um eine unterstützte Tabelle handelt
        $allowedTables = [
            'tl_dc_tanks',
            'tl_dc_regulators',
            'tl_dc_equipment',
        ];

        if (!in_array($itemType, $allowedTables, true)) {
            // Falls die Tabelle nicht erlaubt ist, nichts tun
            $this->logger->error('Ungültige Tabelle: $itemType für Asset ID $assetId', [__METHOD__, TL_ERROR]);
            return;
        }

        // 1. Überprüfen, ob der Status einer der speziellen Werte ist
        $specialStatuses = ['overdue', 'lost', 'damaged', 'missing'];

        if (in_array($reservationStatus, $specialStatuses, true)) {
            // Status direkt in Asset-Tabelle und Reservierungs-Tabelle setzen
            $this->db->update(
                $itemType,                 // Asset-Tabelle
                ['status' => $reservationStatus],
                ['id' => $assetId]
            );

            $this->db->update(
                'tl_dc_reservation_items', // Reservierungs-Tabelle
                ['reservation_status' => $reservationStatus],
                ['id' => $dc->id]
            );

            return; // Keine weitere Verarbeitung erforderlich
        }

        // 2. Standard-Logik für Borrowed/Available
        //$status = $reservationStatus;
        if (!empty($pickedUpAt) && empty($returnedAt)) {
            $status = 'borrowed';
        } elseif (!empty($pickedUpAt) && !empty($returnedAt)) {
            $status = 'available';
            $reservationStatus = 'returned';
        }

        // Status des entsprechenden Assets in der richtigen Tabelle aktualisieren
        $this->db->update(
            $itemType,                  // Tabelle aus item_type
            ['status' => $status],      // Zu setzende Spalten
            ['id'  => $assetId]        // Bedingung (ID des Assets)
        );
        $this->db->update(
            'tl_dc_reservation_items',
            ['reservation_status' => $reservationStatus],
            ['id' => $dc->id]
        );
    }
}
