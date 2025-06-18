<?php

namespace Diversworld\ContaoDiveclubBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class AddDefaultReservationTexts extends AbstractMigration
{
    private Connection $connection;

    private function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getName(): string
    {
        return 'Add default reservation texts';
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        // Stelle sicher, dass die Tabelle existiert
        return $schemaManager->tablesExist(['tl_dc_config']);
    }

    public function run(): MigrationResult
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
        ]);

        return new MigrationResult(true, 'Standardtexte erfolgreich hinzugefügt.');
    }
}
