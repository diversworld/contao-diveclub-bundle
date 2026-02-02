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

        foreach ($exercises as $exerciseId => $data) {
            $exerciseId = (int)$exerciseId;
            if ($exerciseId < 1) {
                continue;
            }

            // Check access
            $access = $db->prepare(
                "SELECT se.id
                 FROM tl_dc_student_exercises se
                 INNER JOIN tl_dc_course_students cs ON cs.id = se.pid
                 LEFT JOIN tl_dc_course_event ce ON ce.id = cs.event_id
                 WHERE se.id = ?
                   AND cs.status = 'active'
                   AND cs.published = '1'
                   AND (ce.id IS NULL OR ce.published = '1')
                   AND (ce.instructor = ? OR se.instructor = ?)
                 LIMIT 1"
            )->execute($exerciseId, $user->id, $user->id);

            if ($access->numRows < 1) {
                continue;
            }

            $newStatus = $data['status'] ?? 'pending';
            if (!isset($GLOBALS['TL_LANG']['tl_dc_student_exercises']['itemStatus'][$newStatus])) {
                $newStatus = 'pending';
            }
            $notes = $data['notes'] ?? '';

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
             WHERE cs.status = 'active'
               AND cs.published = 1
               AND (ce.id IS NULL OR ce.published = 1)
               AND (ce.instructor = ? OR se.instructor = ?)
             ORDER BY s.lastname, s.firstname, ce.dateStart DESC, c.title"
        )->execute($user->id, $user->id);

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
                'modules' => $this->loadStudentModules($db, (int)$assignments->assignment_id, (int)$user->id, $canEditAll),
            ];
        }

        return $students;
    }

    private function loadStudentModules(Database $db, int $assignmentId, int $userId, bool $canEditAll): array
    {
        $filterSql = $canEditAll ? '' : ' AND se.instructor = ?';
        $params = [$assignmentId];

        if (!$canEditAll) {
            $params[] = $userId;
        }

        try {
            $result = $db->prepare(
                "SELECT se.id, se.exercise_id, se.status, se.notes, se.instructor, se.module_id AS student_module_id,
                        ex.title AS exercise_title, m.id AS module_id, m.title AS module_title
                 FROM tl_dc_student_exercises se
                 LEFT JOIN tl_dc_course_exercises ex ON ex.id = se.exercise_id
                 LEFT JOIN tl_dc_course_modules m ON m.id = se.module_id
                 WHERE se.pid = ?{$filterSql}
                 ORDER BY m.sorting, ex.sorting, se.sorting"
            )->execute(...$params);
        } catch (\Exception $e) {
            $result = $db->prepare(
                "SELECT se.id, se.exercise_id, se.status, se.notes, se.instructor, NULL AS student_module_id,
                        ex.title AS exercise_title, m.id AS module_id, m.title AS module_title
                 FROM tl_dc_student_exercises se
                 LEFT JOIN tl_dc_course_exercises ex ON ex.id = se.exercise_id
                 LEFT JOIN tl_dc_course_modules m ON m.id = ex.pid
                 WHERE se.pid = ?{$filterSql}
                 ORDER BY m.sorting, ex.sorting, se.sorting"
            )->execute(...$params);
        }

        $modules = [];

        while ($result->next()) {
            $moduleId = (int)($result->module_id ?: ($result->student_module_id ?? 0));
            $moduleTitle = (string)$result->module_title ?: 'Allgemein';

            if (!isset($modules[$moduleId])) {
                $modules[$moduleId] = [
                    'id' => $moduleId,
                    'title' => $moduleTitle,
                    'exercises' => [],
                ];
            }

            $title = (string)$result->exercise_title;
            if ((int)$result->exercise_id === 0) {
                $title = 'Modul-Abschluss';
            }

            $modules[$moduleId]['exercises'][] = [
                'id' => (int)$result->id,
                'exercise_id' => (int)$result->exercise_id,
                'module_id' => $moduleId,
                'title' => $title,
                'status' => (string)$result->status,
                'notes' => (string)$result->notes,
                'instructor' => $this->resolveInstructorName($db, (int)$result->instructor),
            ];
        }

        return array_values($modules);
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
