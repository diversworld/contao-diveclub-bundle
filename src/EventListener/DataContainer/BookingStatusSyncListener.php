<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_dc_check_booking', target: 'config.onsubmit')] // Registriert die Klasse als onsubmit-Callback für die Tabelle tl_dc_check_booking
class BookingStatusSyncListener // Listener zur Synchronisation des Status zwischen Buchung und Bestellpositionen
{
    public function __construct(private readonly Connection $connection) // Konstruktor mit Dependency Injection der DB-Verbindung
    {
    }

    public function __invoke(DataContainer $dc): void // Methode die beim Speichern des Datensatzes ausgeführt wird
    {
        if (!$dc->activeRecord) { // Falls kein aktiver Datensatz vorhanden ist
            return; // Abbrechen
        }

        $status = $dc->activeRecord->status; // Hole den aktuellen Status aus dem Datensatz
        $bookingId = $dc->activeRecord->id; // Hole die ID der Buchung

        // Update all related orders with the same status
        $this->connection->update( // Führe ein Update auf die Tabelle tl_dc_check_order aus
            'tl_dc_check_order', // Ziel-Tabelle
            ['status' => $status], // Zu setzende Daten (Status)
            ['pid' => $bookingId] // Bedingung (PID der Bestellung entspricht Buchungs-ID)
        ); // Synchronisiert den Status aller Geräte dieser Buchung
    }
}
