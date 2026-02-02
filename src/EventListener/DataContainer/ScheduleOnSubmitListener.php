<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

class ScheduleOnSubmitListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[AsCallback(table: 'tl_dc_course_event_schedule', target: 'config.onsubmit')]
    public function __invoke(DataContainer $dc): void
    {
        if (!$dc->activeRecord) {
            return;
        }

        $this->syncToStudentExercises($dc);
        $this->generateDefaultExercises($dc);
    }

    /**
     * Synchronisiert Änderungen am Zeitplan mit den Übungsergebnissen der Schüler
     */
    private function syncToStudentExercises(DataContainer $dc): void
    {
        $scheduleId = (int) $dc->activeRecord->id;
        $eventId = (int) $dc->activeRecord->pid;
        $moduleId = (int) $dc->activeRecord->module_id;
        $plannedAt = $dc->activeRecord->planned_at;
        $instructor = $dc->activeRecord->instructor;

        if ($moduleId <= 0) {
            return;
        }

        // 0. Übungen im Zeitplan synchronisieren
        $this->connection->executeStatement(
            "UPDATE tl_dc_event_schedule_exercises SET planned_at=?, instructor=? WHERE pid=? AND (planned_at=0 OR planned_at='' OR planned_at IS NULL)",
            [(int)$plannedAt, $instructor, $scheduleId]
        );

        // 1. Alle Zuweisungen für dieses Event finden
        $studentIds = $this->connection->fetchFirstColumn(
            "SELECT id FROM tl_dc_course_students WHERE event_id=?",
            [$eventId]
        );

        if (empty($studentIds)) {
            return;
        }

        // 2. Übungen aus tl_dc_event_schedule_exercises bevorzugen
        $scheduleEx = $this->connection->fetchAllAssociative(
            "SELECT exercise_id, planned_at, instructor FROM tl_dc_event_schedule_exercises WHERE pid=? AND (published='1' OR published=1)",
            [$scheduleId]
        );

        if (!empty($scheduleEx)) {
            foreach ($scheduleEx as $objScheduleEx) {
                $exPlannedAt = $objScheduleEx['planned_at'] ?: $plannedAt;
                $exInstructor = $objScheduleEx['instructor'] ?: $instructor;

                try {
                    $this->connection->fetchOne("SELECT module_id FROM tl_dc_student_exercises LIMIT 1");
                    $this->connection->executeStatement(
                        "UPDATE tl_dc_student_exercises SET instructor=?, dateCompleted=? WHERE pid IN (?) AND exercise_id=? AND module_id=?",
                        [$exInstructor, (int)$exPlannedAt, $studentIds, $objScheduleEx['exercise_id'], $moduleId],
                        [null, null, Connection::PARAM_INT_ARRAY, null, null]
                    );
                } catch (\Exception $e) {
                    $this->connection->executeStatement(
                        "UPDATE tl_dc_student_exercises SET instructor=?, dateCompleted=? WHERE pid IN (?) AND exercise_id=?",
                        [$exInstructor, (int)$exPlannedAt, $studentIds, $objScheduleEx['exercise_id']],
                        [null, null, Connection::PARAM_INT_ARRAY, null]
                    );
                }
            }
        } else {
            // Fallback: Alle Übungen dieses Moduls aus den Stammdaten finden
            $exIds = $this->connection->fetchFirstColumn(
                "SELECT id FROM tl_dc_course_exercises WHERE pid=?",
                [$moduleId]
            );

            if (!empty($exIds)) {
                try {
                    $this->connection->fetchOne("SELECT module_id FROM tl_dc_student_exercises LIMIT 1");
                    $this->connection->executeStatement(
                        "UPDATE tl_dc_student_exercises SET instructor=?, dateCompleted=? WHERE pid IN (?) AND exercise_id IN (?) AND module_id=?",
                        [$instructor, (int)$plannedAt, $studentIds, $exIds, $moduleId],
                        [null, null, Connection::PARAM_INT_ARRAY, Connection::PARAM_INT_ARRAY, null]
                    );
                } catch (\Exception $e) {
                    $this->connection->executeStatement(
                        "UPDATE tl_dc_student_exercises SET instructor=?, dateCompleted=? WHERE pid IN (?) AND exercise_id IN (?)",
                        [$instructor, (int)$plannedAt, $studentIds, $exIds],
                        [null, null, Connection::PARAM_INT_ARRAY, Connection::PARAM_INT_ARRAY]
                    );
                }
            }
        }
    }

    /**
     * Erzeugt Standard-Übungen für einen Zeitplan-Eintrag (Modul)
     */
    private function generateDefaultExercises(DataContainer $dc): void
    {
        if (!$dc->activeRecord->module_id) {
            return;
        }

        $id = (int) ($dc->id ?: $dc->activeRecord->id);
        $scheduleId = $id;
        $moduleId = (int) $dc->activeRecord->module_id;

        // Prüfen, ob bereits Übungen existieren
        $exists = $this->connection->fetchOne(
            "SELECT id FROM tl_dc_event_schedule_exercises WHERE pid=? LIMIT 1",
            [$scheduleId]
        );

        if ($exists) {
            return;
        }

        // Übungen des Moduls aus Stammdaten laden
        $exercises = $this->connection->fetchAllAssociative(
            "SELECT * FROM tl_dc_course_exercises WHERE pid=? ORDER BY sorting",
            [$moduleId]
        );

        $sorting = 128;
        foreach ($exercises as $exercise) {
            $this->connection->insert('tl_dc_event_schedule_exercises', [
                'pid' => $scheduleId,
                'tstamp' => time(),
                'sorting' => $sorting,
                'exercise_id' => (int) $exercise['id'],
                'title' => $exercise['title'],
                'description' => $exercise['description'],
                'required' => $exercise['required'],
                'duration' => $exercise['duration'],
                'notes' => $exercise['notes'],
                'published' => '1'
            ]);
            $sorting += 128;
        }
    }
}
