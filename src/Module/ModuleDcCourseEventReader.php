<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Module;

use Contao\Config;
use Contao\Database;
use Contao\Date;
use Contao\FrontendUser;
use Contao\Input;
use Contao\Module;
use Diversworld\ContaoDiveclubBundle\Model\DcCourseEventModel;

class ModuleDcCourseEventReader extends Module
{
    protected $strTemplate = 'mod_dc_course_event_reader';

    protected function compile(): void
    {
        $db = Database::getInstance();

        $identifier = Input::get('event') ?: Input::get('items');
        if (!$identifier) {
            $this->Template->notFound = true;
            return;
        }

        // Per ID oder Alias laden
        if (is_numeric($identifier)) {
            $event = DcCourseEventModel::findByPk((int)$identifier);
        } else {
            $event = DcCourseEventModel::findOneBy(['alias=?', 'published=?'], [$identifier, 1]);
        }

        if (!$event || (int)$event->published !== 1) {
            $this->Template->notFound = true;
            return;
        }

        $dateFormat = Config::get('datimFormat');
        $this->Template->event = [
            'id' => (int)$event->id,
            'title' => (string)$event->title,
            'alias' => (string)$event->alias,
            'dateStart' => $event->dateStart ? Date::parse($dateFormat, (int)strtotime((string)$event->dateStart)) : '',
            'dateEnd' => $event->dateEnd ? Date::parse($dateFormat, (int)strtotime((string)$event->dateEnd)) : '',
            'price' => (string)$event->price,
            'instructor' => (string)$event->instructor,
            'description' => (string)$event->description,
        ];

        // Zeitplan laden (mit Modul- und Übungsnamen)
        $schedule = $db->prepare(
            'SELECT s.id, s.planned_at, s.location, s.notes, m.title AS module_title, e.title AS exercise_title
             FROM tl_dc_course_event_schedule s
             INNER JOIN tl_dc_course_modules m ON m.id = s.module_id
             INNER JOIN tl_dc_course_exercises e ON e.id = s.exercise_id
             WHERE s.pid = ? AND s.published = 1
             ORDER BY s.planned_at, m.sorting, e.sorting'
        )->execute((int)$event->id);

        $rows = [];
        while ($schedule->next()) {
            $rows[] = [
                'planned_at' => $schedule->planned_at ? Date::parse($dateFormat, (int)strtotime((string)$schedule->planned_at)) : '',
                'location' => (string)$schedule->location,
                'notes' => (string)$schedule->notes,
                'module' => (string)$schedule->module_title,
                'exercise' => (string)$schedule->exercise_title,
            ];
        }
        $this->Template->schedule = $rows;
        $this->Template->hasSchedule = !empty($rows);

        // Einfache Anmeldung: nur eingeloggte Member → verknüpfter Student
        /** @var FrontendUser|null $user */
        $user = FrontendUser::getInstance();
        $this->Template->isLoggedIn = (bool)($user && $user->id);

        $studentId = null;
        if ($this->Template->isLoggedIn) {
            $student = $db->prepare('SELECT id FROM tl_dc_students WHERE memberId=?')->execute((int)$user->id);
            if ($student->numRows > 0) {
                $studentId = (int)$student->id;
            }
        }
        $this->Template->hasStudent = $studentId !== null;

        // Prüfen, ob bereits angemeldet
        $alreadyRegistered = false;
        $assignmentId = null;
        if ($studentId) {
            $check = $db->prepare('SELECT id FROM tl_dc_course_students WHERE pid=? AND event_id=?')
                ->execute($studentId, (int)$event->id);
            if ($check->numRows > 0) {
                $alreadyRegistered = true;
                $assignmentId = (int)$check->id;
            }
        }
        $this->Template->alreadyRegistered = $alreadyRegistered;
        $this->Template->assignmentId = $assignmentId;

        // Verarbeitung der Anmeldung
        if (Input::post('FORM_SUBMIT') === 'dc_event_signup' && $studentId && !$alreadyRegistered) {
            // Anlegen der Zuweisung
            $db->prepare('INSERT INTO tl_dc_course_students (pid, tstamp, course_id, event_id, status, registered_on, published) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute(
                    $studentId,
                    time(),
                    (int)$event->course_id,
                    (int)$event->id,
                    'registered',
                    time(),
                    1
                );

            $newAssignmentId = (int)$db->insertId;

            // Übungen erzeugen (kopiert aus generateDefaultExercises-Logik)
            $ex = $db->prepare(
                'SELECT e.id FROM tl_dc_course_exercises e
                 INNER JOIN tl_dc_course_modules m ON e.pid = m.id
                 WHERE m.pid = ?'
            )->execute((int)$event->course_id);

            while ($ex->next()) {
                $exists = $db->prepare('SELECT id FROM tl_dc_student_exercises WHERE pid=? AND exercise_id=?')
                    ->execute($newAssignmentId, (int)$ex->id);
                if ($exists->numRows < 1) {
                    $db->prepare('INSERT INTO tl_dc_student_exercises (pid, tstamp, exercise_id, status, published) VALUES (?, ?, ?, ?, ?)')
                        ->execute($newAssignmentId, time(), (int)$ex->id, 'pending', 1);
                }
            }

            // Refresh Status
            $this->Template->alreadyRegistered = true;
            $this->Template->assignmentId = $newAssignmentId;
        }
    }
}
