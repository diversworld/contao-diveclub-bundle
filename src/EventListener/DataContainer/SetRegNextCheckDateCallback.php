<?php

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

#[AsCallback(table: 'tl_dc_regulator_control', target: 'fields.actualCheckDate.save')]
class SetRegNextCheckDateCallback
{
    private Connection $db;
    private LoggerInterface $logger;

    public function __construct(Connection $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    public function __invoke($value, DataContainer $dc)
    {
        dump($dc->activeRecord);
        //$this->logger = System::getContainer()->get('monolog.logger.contao.general');
        $this->logger->info('__invoke: Actual Check Date: ' . $value);
        $actualCheckDate = $value;

        // Konvertiere in Unix-Timestamp, falls erforderlich
        if (!is_numeric($value) && strtotime($value) !== false && !empty($value)) {
            $actualCheckDate = strtotime($value);
        }

        // Überprüfen, ob der Wert leer oder ungültig ist
        if (!$actualCheckDate || !is_numeric($actualCheckDate) ) {
            throw new \Exception('actualCheckDate is, gelle invalid: expected a Unix-Timestamp or a valid date format.');
        }

        // Erstelle ein DateTime-Objekt aus dem übergebenen Unix-Timestamp
        $date = new \DateTime();
        $date->setTimestamp((int)$actualCheckDate);

        // Manipuliere das Datum (+1 Jahr)
        $date->modify('+1 year');

        // Konvertiere zurück in Unix-Timestamp
        $nextCheckDate = $date->getTimestamp();

        // Speichere den neuen UNIX-Timestamp in der Datenbank
        $dc->activeRecord->nextCheckDate = $nextCheckDate;

        // Speichere das aktualisierte Record
        // Aktualisiere den Wert in der Datenbank
        $this->db->update(
            'tl_dc_regulator_control',       // Tabelle
            ['nextCheckDate' => $nextCheckDate],   // Zu aktualisierende Spalten/Werte
            ['id' => $dc->activeRecord->id]        // Bedingung (id des aktuellen Datensatzes)
        );

        return $value;
    }
}
