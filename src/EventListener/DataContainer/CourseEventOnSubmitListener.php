<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

class CourseEventOnSubmitListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[AsCallback(table: 'tl_dc_course_event', target: 'config.onsubmit')]
    public function __invoke(DataContainer $dc): void
    {
        if (!$dc->activeRecord || !$dc->activeRecord->course_id) {
            return;
        }

        $id = (int) ($dc->id ?: $dc->activeRecord->id);

        // Prüfen, ob bereits Einträge existieren
        $exists = $this->connection->fetchOne("SELECT id FROM tl_dc_course_event_schedule WHERE pid=? LIMIT 1", [$id]);

        if ($exists) {
            return; // nichts erzeugen, wenn schon vorhanden
        }

        // Alle Module der Kurs-Vorlage ziehen und als Plan-Zeilen anlegen
        $modules = $this->connection->fetchAllAssociative("
            SELECT m.id AS module_id
            FROM tl_dc_course_modules m
            WHERE m.pid = ?
            ORDER BY m.sorting
        ", [(int) $dc->activeRecord->course_id]);

        $sorting = 128;
        foreach ($modules as $module) {
            // Modul-Eintrag im Zeitplan
            $this->connection->insert('tl_dc_course_event_schedule', [
                'pid' => $id,
                'tstamp' => time(),
                'sorting' => $sorting,
                'module_id' => (int) $module['module_id'],
                'published' => '1'
            ]);

            $scheduleId = (int) $this->connection->lastInsertId();

            // Übungen dieses Moduls ebenfalls in den Zeitplan kopieren
            $exercises = $this->connection->fetchAllAssociative("SELECT * FROM tl_dc_course_exercises WHERE pid=? ORDER BY sorting", [(int) $module['module_id']]);

            $exSorting = 128;
            foreach ($exercises as $exercise) {
                $this->connection->insert('tl_dc_event_schedule_exercises', [
                    'pid' => $scheduleId,
                    'tstamp' => time(),
                    'sorting' => $exSorting,
                    'exercise_id' => (int) $exercise['id'],
                    'title' => $exercise['title'],
                    'description' => $exercise['description'],
                    'required' => $exercise['required'],
                    'duration' => $exercise['duration'],
                    'notes' => $exercise['notes'],
                    'published' => '1'
                ]);
                $exSorting += 128;
            }

            $sorting += 128;
        }
    }
}
