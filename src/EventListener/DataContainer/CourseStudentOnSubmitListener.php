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

        // 1. Übungen sammeln
        $exercises = [];

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
                // Erst schauen, ob es spezifische Übungen im Zeitplan für dieses Modul-Event gibt
                $scheduleExRows = $this->connection->fetchAllAssociative(
                    "SELECT exercise_id, title, planned_at, instructor FROM tl_dc_event_schedule_exercises WHERE pid=? ORDER BY sorting",
                    [(int) $objSchedule['id']]
                );

                if (!empty($scheduleExRows)) {
                    foreach ($scheduleExRows as $objScheduleEx) {
                        $exId = (int) $objScheduleEx['exercise_id'];
                        // Eindeutige Kennung für Übung im Kontext dieses Moduls
                        $key = $objSchedule['module_id'] . '_' . $exId;
                        if (!isset($exercises[$key])) {
                            $exercises[$key] = [
                                'id' => $exId,
                                'module_id' => (int) $objSchedule['module_id'],
                                'planned_at' => $objScheduleEx['planned_at'] ?: $objSchedule['planned_at'],
                                'instructor' => $objScheduleEx['instructor'] ?: $objSchedule['instructor']
                            ];
                        }
                    }
                } elseif ((int) $objSchedule['module_id'] > 0) {
                    // Fallback: Alle Übungen dieses Moduls aus Stammdaten laden
                    $modExIds = $this->connection->fetchFirstColumn(
                        "SELECT id FROM tl_dc_course_exercises WHERE pid = ? ORDER BY sorting",
                        [(int) $objSchedule['module_id']]
                    );
                    foreach ($modExIds as $modExId) {
                        $exId = (int) $modExId;
                        $key = $objSchedule['module_id'] . '_' . $exId;
                        if (!isset($exercises[$key])) {
                            $exercises[$key] = [
                                'id' => $exId,
                                'module_id' => (int) $objSchedule['module_id'],
                                'planned_at' => $objSchedule['planned_at'],
                                'instructor' => $objSchedule['instructor']
                            ];
                        }
                    }
                }
            }
        }

        // Fall B: Kein Zeitplan oder keine Übungen im Zeitplan gefunden -> Nutze Kurs-Template
        if (empty($exercises)) {
            $modExRows = $this->connection->fetchAllAssociative(
                "SELECT e.id, e.pid AS module_id
                FROM tl_dc_course_exercises e
                JOIN tl_dc_course_modules m ON e.pid = m.id
                WHERE m.pid = ?
                ORDER BY m.sorting, e.sorting",
                [$courseTemplateId]
            );

            foreach ($modExRows as $objModEx) {
                $exercises[] = [
                    'id' => (int) $objModEx['id'],
                    'module_id' => (int) $objModEx['module_id'],
                    'planned_at' => '',
                    'instructor' => ''
                ];
            }
        }

        // 2. Übungen anlegen (Reihenfolge beibehalten)
        $sorting = 128;
        foreach ($exercises as $exData) {
            $exerciseId = (int) $exData['id'];
            $plannedAt = $exData['planned_at'];
            $instructor = $exData['instructor'];

            // Prüfen, ob die Übung für diese Zuweisung schon existiert
            $checkId = $this->connection->fetchOne(
                "SELECT id FROM tl_dc_student_exercises WHERE pid=? AND exercise_id=? AND module_id=?",
                [$assignmentId, $exerciseId, (int) ($exData['module_id'] ?? 0)]
            );

            if (!$checkId) {
                $this->connection->insert('tl_dc_student_exercises', [
                    'pid' => $assignmentId,
                    'tstamp' => time(),
                    'sorting' => $sorting,
                    'exercise_id' => $exerciseId,
                    'module_id' => (int) ($exData['module_id'] ?? 0),
                    'status' => 'pending',
                    'dateCompleted' => $plannedAt,
                    'instructor' => $instructor,
                    'published' => 1
                ]);
                $sorting += 128;
            } else {
                // Falls sie existiert, aber vielleicht das Datum noch fehlt/anders ist, synchronisieren
                $this->connection->executeStatement(
                    "UPDATE tl_dc_student_exercises SET dateCompleted=?, instructor=? WHERE id=? AND (dateCompleted='' OR dateCompleted IS NULL)",
                    [$plannedAt, $instructor, (int) $checkId]
                );
            }
        }
    }
}
