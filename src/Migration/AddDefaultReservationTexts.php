<?php

namespace Diversworld\ContaoDiveclubBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class AddDefaultReservationTexts extends AbstractMigration // Klasse zur Migration von Standard-Reservierungstexten
{
    private Connection $connection; // Variable für die Datenbankverbindung

    public function __construct(Connection $connection) // Konstruktor mit Dependency Injection der DB-Verbindung
    {
        $this->connection = $connection; // Zuweisung der Verbindung
    }

    public function getName(): string // Gibt den Namen der Migration zurück
    {
        return 'Add default reservation texts'; // Name für die Anzeige im Contao Manager / Installtool
    }

    public function shouldRun(): bool // Prüft, ob die Migration ausgeführt werden sollte
    {
        $schemaManager = $this->connection->createSchemaManager(); // Hole den Schema Manager der Datenbank

        // 1. Tabelle muss existieren
        if (!$schemaManager->tablesExist(['tl_dc_config'])) { // Wenn die Tabelle tl_dc_config noch nicht existiert
            return false; // Migration noch nicht ausführen
        }

        // 2. Migration nur ausführen, wenn die Tabelle noch leer ist
        $count = $this->connection->fetchOne("SELECT COUNT(*) FROM tl_dc_config"); // Zähle die Datensätze in der Konfigurationstabelle

        // Stelle sicher, dass die Tabelle existiert und leer ist
        return (int)$count === 0; // Migration nur bei leerer Tabelle ausführen
    }

    public function run(): MigrationResult // Führt das Einfügen der Standarddaten aus
    {
        // Einfügen der Standardtexte
        $this->connection->executeStatement("
            INSERT INTO tl_dc_config (reservationInfoText, reservationMessage, rentalConditions)
            VALUES (?, ?, ?)
        ", [
            'Reservierung erfolgreich!
             Wenn Du keine weiteren Gegenstände reservieren möchtest, kannst Du die Seite einfach verlassen.
             Die folgenden Gegenstände wurden für Dich reserviert:
             %s',
            'Hallo,
             es wurde eine neue Reservierung von #memberName# erstellt.
             Reservierungsnummer: #reservationNumber#
             Reservierte Gegenstände:
             #assetsHtml#
             Leihgebühr gesamt: #totalFee# €
             Mit freundlichen Grüßen,
             ',
            'Bei Reservierung für den Urlaub sind 25€ Anzahlung und bei der Abholung sind 50% der Leihkosten zu zahlen. Mit der Reservierung bestätige ich die Allgemeinen Verleihbedingungen sorgfältig gelesen, verstanden zu haben und sie in allen Punkten anzuerkennen.'
        ]); // Führt das SQL-Statement mit den Standardtexten aus

        return new MigrationResult(true, 'Standardtexte erfolgreich hinzugefügt.'); // Gib Erfolgmeldung zurück
    }
}
