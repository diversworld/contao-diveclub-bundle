<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Date;
use Contao\Input;
use Doctrine\DBAL\Connection;

use Contao\System;

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
        return sprintf('%s — Modul: %s', $date, $label);
    }

    #[AsCallback(table: 'tl_dc_course_event_schedule', target: 'list.sorting.child_record')]
    public function listRecords(array $row): string
    {
        $date = $row['planned_at'] ? Date::parse(Config::get('datimFormat'), (int)$row['planned_at']) : '-';

        // Hole Modul-Titel falls vorhanden
        $module = $row['module_id'];
        if ($row['module_id'] > 0) {
            $moduleData = $this->connection->fetchAssociative(
                "SELECT title FROM tl_dc_course_modules WHERE id = ?",
                [$row['module_id']]
            );
            if ($moduleData) {
                $module = $moduleData['title'];
            }
        }

        return sprintf('<div class="tl_content_left">%s — Modul: %s</div>', $date, $module);
    }
}
