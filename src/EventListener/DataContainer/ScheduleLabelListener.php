<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Date;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_dc_course_event_schedule', target: 'list.label.label')] // Registriert die Klasse als Label-Callback für die Tabelle tl_dc_course_event_schedule
class ScheduleLabelListener // Listener zur Formatierung der Kurs-Termine in der Liste
{
    public function __construct(private readonly Connection $connection) // Konstruktor mit DB-Verbindung (derzeit ungenutzt)
    {
    }

    public function __invoke(array $row, string $label, DataContainer $dc, ?array $args = null): array|string // Methode zur Label-Formatierung
    {
        if (null !== $args) { // Falls Spalten-Argumente vorhanden sind
            if ($args[0]) { // Wenn ein Datum in der ersten Spalte vorhanden ist
                $args[0] = Date::parse(Config::get('datimFormat'), (int)$args[0]); // Formatiere den Zeitstempel nach Contao-Einstellung
            }

            return $args; // Gib formatierte Argumente zurück
        }

        $date = $row['planned_at'] ? Date::parse(Config::get('datimFormat'), (int)$row['planned_at']) : '-'; // Formatiere das Datum für die Standard-Anzeige
        return sprintf('%s — %s', $date, $label); // Gib kombinierten String aus Datum und Label zurück
    }
}
