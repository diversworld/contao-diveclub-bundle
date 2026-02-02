<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

class CourseStudentOnSubmitListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[AsCallback(table: 'tl_dc_course_students', target: 'config.onsubmit')]
    public function __invoke(DataContainer $dc): void
    {
        if (!$dc->activeRecord) {
            return;
        }

        $id = (int) ($dc->id ?: $dc->activeRecord->id);
        $assignmentId = $id;
        $courseTemplateId = (int) $dc->activeRecord->course_id;

        // Wenn eine Veranstaltung gewählt wurde, nutze deren Kursvorlage
        if ((int) $dc->activeRecord->event_id > 0) {
            $eventCourseId = (int) $this->connection->fetchOne(
                "SELECT course_id FROM tl_dc_course_event WHERE id=?",
                [(int) $dc->activeRecord->event_id]
            );

            if ($eventCourseId > 0) {
                $courseTemplateId = $eventCourseId;

                // Falls course_id in der Zuweisung noch leer ist, jetzt setzen
                if (!(int) $dc->activeRecord->course_id) {
                    $this->connection->update(
                        'tl_dc_course_students',
                        ['course_id' => $courseTemplateId],
                        ['id' => $assignmentId]
                    );
                }
            }
        }

        if ($courseTemplateId <= 0) {
            return;
        }

        // 1. Module und Übungen sammeln
        $moduleExercises = [];
        $allModules = [];

        // Fall A: Es gibt einen Zeitplan (tl_dc_course_event_schedule)
        if ((int) $dc->activeRecord->event_id > 0) {
            $scheduleRows = $this->connection->fetchAllAssociative(
                "SELECT s.id, s.module_id, s.planned_at, s.instructor
                FROM tl_dc_course_event_schedule s
                WHERE s.pid = ?
                ORDER BY s.planned_at ASC, s.sorting ASC",
                [(int) $dc->activeRecord->event_id]
            );

            foreach ($scheduleRows as $objSchedule) {
                $moduleId = (int)$objSchedule['module_id'];
                if ($moduleId <= 0) continue;

                if (!isset($allModules[$moduleId])) {
                    $allModules[$moduleId] = [
                        'planned_at' => $objSchedule['planned_at'],
                        'instructor' => $objSchedule['instructor']
                    ];
                }

                // Erst schauen, ob es spezifische Übungen im Zeitplan für dieses Modul-Event gibt
                $scheduleExRows = $this->connection->fetchAllAssociative(
                    "SELECT exercise_id, title, planned_at, instructor FROM tl_dc_event_schedule_exercises WHERE pid=? ORDER BY sorting",
                    [(int) $objSchedule['id']]
                );

                if (!empty($scheduleExRows)) {
                    foreach ($scheduleExRows as $objScheduleEx) {
                        $exId = (int) $objScheduleEx['exercise_id'];
                        // Eindeutige Kennung für Übung im Kontext dieses Moduls
                        $key = $moduleId . '_' . $exId;
                        if (!isset($moduleExercises[$key])) {
                            $moduleExercises[$key] = [
                                'id' => $exId,
                                'module_id' => $moduleId,
                                'planned_at' => $objScheduleEx['planned_at'] ?: $objSchedule['planned_at'],
                                'instructor' => $objScheduleEx['instructor'] ?: $objSchedule['instructor']
                            ];
                        }
                    }
                } else {
                    // Fallback: Alle Übungen dieses Moduls aus Stammdaten laden
                    $modExIds = $this->connection->fetchFirstColumn(
                        "SELECT id FROM tl_dc_course_exercises WHERE pid = ? ORDER BY sorting",
                        [$moduleId]
                    );
                    foreach ($modExIds as $modExId) {
                        $exId = (int) $modExId;
                        $key = $moduleId . '_' . $exId;
                        if (!isset($moduleExercises[$key])) {
                            $moduleExercises[$key] = [
                                'id' => $exId,
                                'module_id' => $moduleId,
                                'planned_at' => $objSchedule['planned_at'],
                                'instructor' => $objSchedule['instructor']
                            ];
                        }
                    }
                }
            }
        }

        // Fall B: Kein Zeitplan oder keine Übungen im Zeitplan gefunden -> Nutze Kurs-Template
        if (empty($allModules)) {
            $modules = $this->connection->fetchAllAssociative(
                "SELECT id FROM tl_dc_course_modules WHERE pid = ? ORDER BY sorting",
                [$courseTemplateId]
            );

            foreach ($modules as $mod) {
                $moduleId = (int)$mod['id'];
                $allModules[$moduleId] = ['planned_at' => '', 'instructor' => ''];

                $modExRows = $this->connection->fetchAllAssociative(
                    "SELECT id FROM tl_dc_course_exercises WHERE pid = ? ORDER BY sorting",
                    [$moduleId]
                );

                foreach ($modExRows as $objModEx) {
                    $exId = (int)$objModEx['id'];
                    $key = $moduleId . '_' . $exId;
                    $moduleExercises[$key] = [
                        'id' => $exId,
                        'module_id' => $moduleId,
                        'planned_at' => '',
                        'instructor' => ''
                    ];
                }
            }
        }

        // 2. Einträge anlegen (Reihenfolge: Modulweise)
        $sorting = 128;
        foreach ($allModules as $moduleId => $modData) {
            // Find exercises for this module
            $exercisesForThisModule = [];
            foreach ($moduleExercises as $key => $exData) {
                if ($exData['module_id'] === $moduleId) {
                    $exercisesForThisModule[] = $exData;
                }
            }

            if (empty($exercisesForThisModule)) {
                // Modul-Eintrag ohne Übung anlegen
                $this->upsertEntry($assignmentId, 0, $moduleId, $modData['planned_at'], $modData['instructor'], $sorting);
                $sorting += 128;
            } else {
                foreach ($exercisesForThisModule as $exData) {
                    $this->upsertEntry($assignmentId, $exData['id'], $moduleId, $exData['planned_at'], $exData['instructor'], $sorting);
                    $sorting += 128;
                }
            }
        }
    }

    private function upsertEntry(int $assignmentId, int $exerciseId, int $moduleId, $plannedAt, $instructor, int $sorting): void
    {
        // Prüfen, ob der Eintrag für diese Zuweisung schon existiert
        $checkSql = "SELECT id FROM tl_dc_student_exercises WHERE pid=? AND exercise_id=? AND module_id=?";
        $checkParams = [$assignmentId, $exerciseId, $moduleId];

        $checkId = $this->connection->fetchOne($checkSql, $checkParams);

        if (!$checkId) {
            $insertData = [
                'pid' => $assignmentId,
                'tstamp' => time(),
                'sorting' => $sorting,
                'exercise_id' => $exerciseId,
                'module_id' => $moduleId,
                'status' => 'pending',
                'dateCompleted' => (int)$plannedAt,
                'instructor' => $instructor,
                'published' => 1
            ];

            $this->connection->insert('tl_dc_student_exercises', $insertData);
        } else {
            // Falls er existiert, aber vielleicht das Datum noch fehlt/anders ist, synchronisieren
            $this->connection->executeStatement(
                "UPDATE tl_dc_student_exercises SET dateCompleted=?, instructor=? WHERE id=? AND (dateCompleted=0 OR dateCompleted='' OR dateCompleted IS NULL)",
                [(int)$plannedAt, $instructor, (int)$checkId]
            );
        }
    }
}
