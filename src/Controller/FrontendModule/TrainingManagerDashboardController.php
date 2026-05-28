<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Database;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller für das Frontend-Modul "Ausbildungsleiter Dashboard".
 */
#[AsFrontendModule('dc_training_manager_dashboard', category: 'dc_manager', template: 'frontend_module/mod_dc_training_manager_dashboard')]
class TrainingManagerDashboardController extends AbstractFrontendModuleController
{
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $user = System::getContainer()->get('security.helper')->getUser();
        if (!$user instanceof FrontendUser) {
            return new Response('Access Denied', Response::HTTP_FORBIDDEN);
        }

        $db = Database::getInstance();
        $config = $db->prepare("SELECT training_manager, dashboard_options FROM tl_dc_config WHERE published='1' LIMIT 1")->execute();

        if ($config->numRows < 1 || (int)$config->training_manager !== (int)$user->id) {
            return new Response('Access Denied', Response::HTTP_FORBIDDEN);
        }

        $options = StringUtil::deserialize($config->dashboard_options, true);
        $template->options = $options;
        $template->noOptions = empty($options);
        $template->courses = [];
        $template->workload = [];

        if ($template->noOptions) {
            return $template->getResponse();
        }

        $courses = $this->loadActiveCourses($db);
        $template->courses = $courses;

        if (in_array('workload', $options, true)) {
            $template->workload = $this->calculateWorkload($courses);
        }

        return $template->getResponse();
    }

    private function loadActiveCourses(Database $db): array
    {
        // Laufende Kurse (Events), die veröffentlicht sind
        $events = $db->prepare(
            "SELECT ce.id, ce.title, ce.dateStart, ce.dateEnd, ce.instructor, ce.course_id, c.title as course_title
             FROM tl_dc_course_event ce
             LEFT JOIN tl_dc_dive_course c ON ce.course_id = c.id
             WHERE ce.published = '1'
             ORDER BY ce.dateStart DESC"
        )->execute();

        $result = [];

        while ($events->next()) {
            $eventId = (int)$events->id;
            $courseId = (int)$events->course_id;

            $instructorName = $this->resolveMemberName($db, (int)$events->instructor);

            // Schüler für dieses Event laden
            $students = $this->loadStudentsForEvent($db, $eventId, $courseId);

            $result[] = [
                'id' => $eventId,
                'title' => $events->title,
                'course_title' => $events->course_title,
                'dateStart' => $events->dateStart,
                'dateEnd' => $events->dateEnd,
                'instructor_id' => (int)$events->instructor,
                'instructor_name' => $instructorName,
                'students' => $students
            ];
        }

        return $result;
    }

    private function loadStudentsForEvent(Database $db, int $eventId, int $courseId): array
    {
        $students = $db->prepare(
            "SELECT cs.id as assignment_id, s.firstname, s.lastname, cs.status
             FROM tl_dc_course_students cs
             JOIN tl_dc_students s ON cs.pid = s.id
             WHERE cs.event_id = ? AND cs.published = '1'"
        )->execute($eventId);

        $list = [];

        // Gesamtanzahl der Übungen für diesen Kurs ermitteln
        $totalExercises = $this->getTotalExercisesCount($db, $courseId);

        while ($students->next()) {
            $assignmentId = (int)$students->assignment_id;

            // Abgeschlossene Übungen für diesen Schüler
            $completedExercises = $db->prepare(
                "SELECT count(id) as count FROM tl_dc_student_exercises WHERE pid = ? AND status = 'ok'"
            )->execute($assignmentId)->count;

            // Details zum Fortschritt (welche Übungen noch offen sind)
            $progressDetails = $this->getStudentProgressDetails($db, $assignmentId, $courseId);

            $list[] = [
                'name' => trim($students->firstname . ' ' . $students->lastname),
                'status' => $students->status,
                'progress' => ($totalExercises > 0) ? round(($completedExercises / $totalExercises) * 100) : 0,
                'completed' => $completedExercises,
                'total' => $totalExercises,
                'details' => $progressDetails
            ];
        }

        return $list;
    }

    private function getTotalExercisesCount(Database $db, int $courseId): int
    {
        $result = $db->prepare(
            "SELECT count(e.id) as count
             FROM tl_dc_course_exercises e
             JOIN tl_dc_course_modules m ON e.pid = m.id
             WHERE m.pid = ?"
        )->execute($courseId);

        return (int)$result->count;
    }

    private function getStudentProgressDetails(Database $db, int $assignmentId, int $courseId): array
    {
        // Alle Module und Übungen des Kurses
        $modules = $db->prepare(
            "SELECT m.id as module_id, m.title as module_title
             FROM tl_dc_course_modules m
             WHERE m.pid = ?
             ORDER BY m.sorting"
        )->execute($courseId);

        $details = [];

        while ($modules->next()) {
            $moduleId = (int)$modules->module_id;

            $exercises = $db->prepare(
                "SELECT e.id as exercise_id, e.title as exercise_title
                 FROM tl_dc_course_exercises e
                 WHERE e.pid = ?
                 ORDER BY e.sorting"
            )->execute($moduleId);

            $exerciseList = [];
            while ($exercises->next()) {
                $exerciseId = (int)$exercises->exercise_id;

                // Status der Übung für den Schüler prüfen
                $status = $db->prepare(
                    "SELECT status FROM tl_dc_student_exercises WHERE pid = ? AND exercise_id = ?"
                )->execute($assignmentId, $exerciseId);

                $exerciseList[] = [
                    'title' => $exercises->exercise_title,
                    'status' => $status->numRows > 0 ? $status->status : 'pending'
                ];
            }

            $details[] = [
                'title' => $modules->module_title,
                'exercises' => $exerciseList
            ];
        }

        return $details;
    }

    private function calculateWorkload(array $courses): array
    {
        $workload = [];

        foreach ($courses as $course) {
            $name = $course['instructor_name'] ?: 'Nicht zugewiesen';
            if (!isset($workload[$name])) {
                $workload[$name] = 0;
            }
            $workload[$name]++;
        }

        return $workload;
    }

    private function resolveMemberName(Database $db, int $memberId): string
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
}
