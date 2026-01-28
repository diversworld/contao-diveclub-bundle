<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Date;
use Doctrine\DBAL\Connection;

class ScheduleLabelListener // Listener zur Formatierung der Kurs-Termine in der Liste
{
    public function __construct(private readonly Connection $connection) // Konstruktor mit DB-Verbindung (derzeit ungenutzt)
    {
    }

    #[AsCallback(table: 'tl_dc_course_event_schedule', target: 'list.label.label')]
    public function __invoke(array $row, string $label, DataContainer $dc, ?array $args = null): array|string // Methode zur Label-Formatierung
    {
        if (null !== $args) {
            // $args[0] = planned_at, $args[1] = module_id (formatted as title via foreignKey)
            if ($args[0]) {
                $args[0] = Date::parse(Config::get('datimFormat'), (int)$args[0]);
            }

            return $args;
        }

        $date = $row['planned_at'] ? Date::parse(Config::get('datimFormat'), (int)$row['planned_at']) : '-';
        return sprintf('%s — %s', $date, $label);
    }
}
