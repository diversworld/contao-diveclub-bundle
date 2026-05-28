<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Controller\FrontendModule;

use Contao\Config;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Database;
use Contao\Date;
use Contao\FrontendUser;
use Contao\Input;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller für das Frontend-Modul "Kurslehrer-Übersicht".
 * Ermöglicht es Ausbildern, den Fortschritt ihrer Schüler zu sehen und Übungen abzuzeichnen.
 */
#[AsFrontendModule('dc_course_instructor', category: 'dc_manager', template: 'frontend_module/mod_dc_course_instructor')]
class CourseInstructorController extends AbstractFrontendModuleController
{
    /**
     * Verarbeitet die Anfrage und gibt die Antwort zurück.
     */
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $user = System::getContainer()->get('security.helper')->getUser();
        $isInstructor = $this->isInstructor($user);

        if (!$isInstructor) {
            return new Response('Access Denied', Response::HTTP_FORBIDDEN);
        }

        if ($user instanceof FrontendUser && $request->isMethod('POST')) {
            $this->processForm($request, $user);
            // Redirect to avoid form resubmission
            return new Response('', 303, ['Location' => $request->getUri()]);
        }

        $db = Database::getInstance();
        $dateFormat = Config::get('dateFormat');
        $datimFormat = Config::get('datimFormat');

        $students = $user instanceof FrontendUser
            ? $this->loadActiveCoursesByStudent($db, $user, $dateFormat, $datimFormat)
            : [];

        if (empty($students)) {
            $template->notFound = true;
            return $template->getResponse();
        }

        $template->notFound = false;
        $template->students = array_values($students);
        $template->isInstructor = $isInstructor;
        System::loadLanguageFile('tl_dc_student_exercises');
        $template->statusOptions = $GLOBALS['TL_LANG']['tl_dc_student_exercises']['itemStatus'] ?? [];
        $template->request_token = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
        $template->action = $request->getUri();

        return $template->getResponse();
    }

    /**
     * Prüft, ob der aktuelle Benutzer ein Ausbilder ist.
     */
    private function isInstructor($user): bool
    {
        if (!$user instanceof FrontendUser) {
            return false;
        }

        $groups = StringUtil::deserialize($user->groups, true);
        $db = Database::getInstance();
        $configResult = $db->prepare("SELECT instructor_groups FROM tl_dc_config WHERE published='1' LIMIT 1")->execute();

        $instructorGroups = [];
        if ($configResult->numRows > 0) {
            $instructorGroups = StringUtil::deserialize($configResult->instructor_groups, true);
        }

        if (empty($instructorGroups)) {
            $instructorGroups = ['2', '3'];
        }

        foreach ($instructorGroups as $groupId) {
            if (in_array((string)$groupId, $groups, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verarbeitet das Formular-Submit zum Abzeichnen von Übungen.
     */
    private function processForm(Request $request, FrontendUser $user): void
    {
        if ($request->request->get('FORM_SUBMIT') !== 'dc_course_instructor') {
            return;
        }

        $exercises = $request->request->all('exercises');
        if (empty($exercises)) {
            return;
        }

        $db = Database::getInstance();
        System::loadLanguageFile('tl_dc_student_exercises');

        foreach ($exercises as $key => $data) {
            $isNew = str_starts_with((string)$key, 'new:');
            $exerciseId = 0;
            $assignmentId = 0;
            $moduleId = 0;
            $realExerciseId = 0;

            if ($isNew) {
                $parts = explode(':', (string)$key);
                if (count($parts) !== 4) {
                    continue;
                }
                $assignmentId = (int)$parts[1];
                $moduleId = (int)$parts[2];
                $realExerciseId = (int)$parts[3];
            } else {
                $exerciseId = (int)$key;
            }

            if (!$isNew && $exerciseId < 1) {
                continue;
            }

            // Check access
            if ($isNew) {
                $access = $db->prepare(
                    "SELECT cs.id
                     FROM tl_dc_course_students cs
                     LEFT JOIN tl_dc_course_event ce ON ce.id = cs.event_id
                     LEFT JOIN tl_dc_course_event_schedule ces ON ces.pid = ce.id
                     LEFT JOIN tl_dc_event_schedule_exercises ese ON ese.pid = ces.id
                     WHERE cs.id = ?
                       AND cs.status = 'active'
                       AND cs.published = '1'
                       AND (ce.id IS NULL OR ce.published = '1')
                       AND (ce.instructor = ? OR ese.instructor = ?)"
                )->execute($assignmentId, $user->id, $user->id);
            } else {
                $access = $db->prepare(
                    "SELECT se.id
                     FROM tl_dc_student_exercises se
                     INNER JOIN tl_dc_course_students cs ON cs.id = se.pid
                     LEFT JOIN tl_dc_course_event ce ON ce.id = cs.event_id
                     LEFT JOIN tl_dc_course_event_schedule ces ON ces.pid = ce.id
                     LEFT JOIN tl_dc_event_schedule_exercises ese ON ese.pid = ces.id
                     WHERE se.id = ?
                       AND cs.status = 'active'
                       AND cs.published = '1'
                       AND (ce.id IS NULL OR ce.published = '1')
                       AND (ce.instructor = ? OR se.instructor = ? OR ese.instructor = ?)"
                )->execute($exerciseId, $user->id, $user->id, $user->id);
            }

            if ($access->numRows < 1) {
                continue;
            }

            $newStatus = $data['status'] ?? 'pending';
            if (!isset($GLOBALS['TL_LANG']['tl_dc_student_exercises']['itemStatus'][$newStatus])) {
                $newStatus = 'pending';
            }
            $notes = $data['notes'] ?? '';

            if ($isNew) {
                if ($newStatus === 'pending' && empty($notes)) {
                    continue; // Nichts zu tun für neue Übung im Status pending ohne Notiz
                }
                $completedAt = ($newStatus === 'ok') ? time() : 0;
                $db->prepare("INSERT INTO tl_dc_student_exercises (pid, module_id, exercise_id, status, notes, dateCompleted, instructor, tstamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                    ->execute($assignmentId, $moduleId, $realExerciseId, $newStatus, $notes, $completedAt, $user->id, time());
            } else {
                $current = $db->prepare("SELECT status, dateCompleted FROM tl_dc_student_exercises WHERE id=?")->execute($exerciseId);
                $completedAt = $current->numRows > 0 ? $current->dateCompleted : '0';

                if ($newStatus === 'ok') {
                    if ($completedAt === null || $completedAt === '' || $completedAt === '0' || $completedAt === 0) {
                        $completedAt = time();
                    }
                } else {
                    $completedAt = 0;
                }

                $db->prepare("UPDATE tl_dc_student_exercises SET status=?, notes=?, dateCompleted=?, instructor=?, tstamp=? WHERE id=?")
                    ->execute($newStatus, $notes, $completedAt, $user->id, time(), $exerciseId);
            }
        }
    }

    private function loadActiveCoursesByStudent(Database $db, FrontendUser $user, string $dateFormat, string $datimFormat): array
    {
        $assignments = $db->prepare(
            "SELECT DISTINCT cs.id AS assignment_id, cs.pid AS student_id, cs.course_id, cs.event_id, cs.status, cs.registered_on,
                    cs.payed, cs.brevet, cs.dateBrevet, cs.notes AS assignment_notes,
                    s.firstname, s.lastname, s.email,
                    ce.title AS event_title, ce.dateStart, ce.dateEnd, ce.instructor AS event_instructor,
                    c.title AS course_title
             FROM tl_dc_course_students cs
             INNER JOIN tl_dc_students s ON s.id = cs.pid
             LEFT JOIN tl_dc_course_event ce ON ce.id = cs.event_id
             LEFT JOIN tl_dc_dive_course c ON c.id = cs.course_id
             LEFT JOIN tl_dc_student_exercises se ON se.pid = cs.id
             LEFT JOIN tl_dc_course_event_schedule ces ON ces.pid = ce.id
             LEFT JOIN tl_dc_event_schedule_exercises ese ON ese.pid = ces.id
             WHERE cs.status = 'active'
               AND cs.published = 1
               AND (ce.id IS NULL OR ce.published = 1)
               AND (ce.instructor = ? OR se.instructor = ? OR ese.instructor = ?)
             ORDER BY s.lastname, s.firstname, ce.dateStart DESC, c.title"
        )->execute($user->id, $user->id, $user->id);

        $students = [];

        while ($assignments->next()) {
            $studentId = (int)$assignments->student_id;

            if (!isset($students[$studentId])) {
                $students[$studentId] = [
                    'id' => $studentId,
                    'firstname' => (string)$assignments->firstname,
                    'lastname' => (string)$assignments->lastname,
                    'name' => trim($assignments->firstname . ' ' . $assignments->lastname),
                    'email' => (string)$assignments->email,
                    'courses' => [],
                ];
            }

            $canEditAll = ((int)$assignments->event_instructor === (int)$user->id);

            $students[$studentId]['courses'][] = [
                'assignment_id' => (int)$assignments->assignment_id,
                'status' => (string)$assignments->status,
                'registered_on' => $this->formatTimestamp($assignments->registered_on, $dateFormat),
                'payed' => (bool)$assignments->payed,
                'brevet' => (bool)$assignments->brevet,
                'dateBrevet' => $this->formatTimestamp($assignments->dateBrevet, $dateFormat),
                'notes' => (string)$assignments->assignment_notes,
                'course' => [
                    'id' => (int)$assignments->course_id,
                    'title' => (string)$assignments->course_title,
                ],
                'event' => [
                    'id' => (int)$assignments->event_id,
                    'title' => (string)$assignments->event_title,
                    'dateStart' => $this->formatTimestamp($assignments->dateStart, $datimFormat),
                    'dateEnd' => $this->formatTimestamp($assignments->dateEnd, $datimFormat),
                    'instructor' => $this->resolveInstructorName($db, (int)$assignments->event_instructor),
                ],
                'modules' => $this->loadStudentModules($db, (int)$assignments->assignment_id, (int)$assignments->course_id, (int)$user->id, $canEditAll),
            ];
        }

        return $students;
    }

    private function loadStudentModules(Database $db, int $assignmentId, int $courseId, int $userId, bool $canEditAll): array
    {
        // 1. Alle Module und Übungen des Kurses laden
        $exercisesResult = $db->prepare(
            "SELECT m.id AS module_id, m.title AS module_title, ex.id AS exercise_id, ex.title AS exercise_title
             FROM tl_dc_course_modules m
             LEFT JOIN tl_dc_course_exercises ex ON ex.pid = m.id AND ex.published = '1'
             WHERE m.pid = ? AND m.published = '1'
             ORDER BY m.sorting, ex.sorting"
        )->execute($courseId);

        $courseStructure = [];
        while ($exercisesResult->next()) {
            $mId = (int)$exercisesResult->module_id;
            if (!isset($courseStructure[$mId])) {
                $courseStructure[$mId] = [
                    'id' => $mId,
                    'title' => (string)$exercisesResult->module_title,
                    'exercises' => []
                ];
            }
            if ($exercisesResult->exercise_id) {
                $courseStructure[$mId]['exercises'][(int)$exercisesResult->exercise_id] = [
                    'exercise_id' => (int)$exercisesResult->exercise_id,
                    'title' => (string)$exercisesResult->exercise_title,
                ];
            }
        }

        // 2. Bestehende Fortschritte laden
        $progressResult = $db->prepare(
            "SELECT se.id, se.exercise_id, se.module_id, se.status, se.notes, se.instructor
             FROM tl_dc_student_exercises se
             WHERE se.pid = ?"
        )->execute($assignmentId);

        $progress = [];
        while ($progressResult->next()) {
            if ($progressResult->exercise_id > 0) {
                $progress['ex_' . $progressResult->exercise_id] = $progressResult->row();
            } else {
                $progress['mod_' . $progressResult->module_id] = $progressResult->row();
            }
        }

        // 3. Kombinieren
        $modules = [];
        foreach ($courseStructure as $mId => $mDetails) {
            $moduleData = [
                'id' => $mId,
                'title' => $mDetails['title'],
                'exercises' => []
            ];

            // Übungen des Moduls
            foreach ($mDetails['exercises'] as $exId => $exDetails) {
                $prog = $progress['ex_' . $exId] ?? null;

                $moduleData['exercises'][] = [
                    'id' => $prog ? (int)$prog['id'] : 0,
                    'exercise_id' => $exId,
                    'module_id' => $mId,
                    'title' => $exDetails['title'],
                    'status' => $prog ? (string)$prog['status'] : 'pending',
                    'notes' => $prog ? (string)$prog['notes'] : '',
                    'instructor' => $prog ? $this->resolveInstructorName($db, (int)$prog['instructor']) : '',
                ];
            }

            // Modul-Abschluss hinzufügen
            $prog = $progress['mod_' . $mId] ?? null;
            $moduleData['exercises'][] = [
                'id' => $prog ? (int)$prog['id'] : 0,
                'exercise_id' => 0,
                'module_id' => $mId,
                'title' => 'Modul-Abschluss',
                'status' => $prog ? (string)$prog['status'] : 'pending',
                'notes' => $prog ? (string)$prog['notes'] : '',
                'instructor' => $prog ? $this->resolveInstructorName($db, (int)$prog['instructor']) : '',
            ];

            $modules[] = $moduleData;
        }

        return $modules;
    }

    private function resolveInstructorName(Database $db, int $memberId): string
    {
        if ($memberId < 1) {
            return '';
        }

        $member = $db->prepare("SELECT firstname, lastname FROM tl_member WHERE id=?")->execute($memberId);
        if ($member->numRows < 1) {
            return '';
        }

        return trim($member->firstname . ' ' . $member->lastname);
    }

    private function formatTimestamp($value, string $format): string
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return '';
        }

        if (is_numeric($value)) {
            $ts = (int)$value;
        } else {
            $parsed = strtotime((string)$value);
            if ($parsed === false || $parsed <= 0) {
                return (string)$value;
            }
            $ts = $parsed;
        }

        if ($ts <= 0) {
            return '';
        }

        return Date::parse($format, $ts);
    }
}
