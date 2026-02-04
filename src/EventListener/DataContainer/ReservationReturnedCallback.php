<?php


namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Diversworld\ContaoDiveclubBundle\Model\DcReservationItemsModel;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

#[AsCallback(table: 'tl_dc_reservation', target: 'fields.returned_at.save')]
class ReservationReturnedCallback
{
    private Connection $db;
    private LoggerInterface $logger;

    public function __construct(Connection $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function __invoke($value, DataContainer $dc): mixed
    {
        if (!$dc->activeRecord) {
            return '-';
        }

        // Datum im Format jjjjmmtt
        $currentDate = $value;

        if ($value)
		{
			// Neues Title-Format
			$newStatus = 'returned';
			$itemStatus = 'available';
		} else
		{
			return $value;
		}

        try {
            $dc->activeRecord->reservation_status = $newStatus;

            // Verbundene Reservierungselemente (Assets) abrufen:
            $reservationItems = DcReservationItemsModel::findBy('pid', $dc->id); // Alle Items für die Reservierung holen
            if ($reservationItems === null) {
                $this->logger->info(sprintf('Keine Reservierungsitems gefunden für Reservierung ID: %d', $dc->id), [__METHOD__]);
                return $value; // Keine Items verknüpft, keine Änderungen
            }

            foreach ($reservationItems as $reservationItem) {
                // Name der Tabelle basierend auf `item_type` definieren
                $tableName = $reservationItem->item_type;

                // Unterstützte Tabellen überprüfen
                $allowedTables = [
                    'tl_dc_tanks',
                    'tl_dc_regulators',
                    'tl_dc_equipment',
                ];

                if (!in_array($tableName, $allowedTables, true)) {
                    $this->logger->error(sprintf('Ungültige Tabelle: %s für Asset ID: %d', $tableName, $reservationItem->item_id), [__METHOD__]);
                    continue; // Überspringen, wenn Tabelle nicht unterstützt
                }

                // Status in der Asset-Tabelle aktualisieren
                $this->db->update(
                    $tableName, // Entsprechende Tabelle
                    [
                        'status' => $itemStatus,
                    ],
                    ['id' => $reservationItem->item_id] // Bedingung: ID des Items
                );

                // Protokollierung der Aktualisierung (Debugging)
                $this->logger->info(sprintf(
                    'Status von Asset ID %d in Tabelle %s auf %s geändert.',
                    $reservationItem->item_id,
                    $tableName,
                    $itemStatus
                ));
            }

            // Status der Reservierungsitems aktualisieren
            $this->db->update(
                'tl_dc_reservation_items',
                [
                    'reservation_status' => $newStatus,
                    'updated_at' => $currentDate,
                    'returned_at' => $currentDate,
                ],
                ['pid' => $dc->id] // Alle Items der aktuellen Reservierung
            );

            // Status der Hauptreservierung aktualisieren
            $this->db->update(
                'tl_dc_reservation',
                [
                    'reservation_status' => $newStatus,
                ],
                ['id' => $dc->id]
            );

        } catch (\Exception $e) {
            // Fehlerprotokollierung
            $this->logger->error(sprintf(
                'Fehler beim Aktualisieren der Assets für Reservierung ID %d: %s',
                $dc->id,
                $e->getMessage()
            ), [__METHOD__]);
        }
        return $value;
    }
}
