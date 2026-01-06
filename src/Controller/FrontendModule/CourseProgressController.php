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
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule('dc_course_progress', category: 'dc_manager', template: 'frontend_module/mod_dc_course_progress')]
class CourseProgressController extends AbstractFrontendModuleController
{
    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        /** @var FrontendUser|null $user */
        $user = System::getContainer()->get('security.helper')->getUser();

        System::getContainer()->get('monolog.logger.contao.general')->info('CourseProgressController::getResponse start. User ID: ' . ($user ? $user->id : 'none'));

        // Debug: Tabellen prüfen
        try {
            $dbCheck = Database::getInstance();
            $tables = $dbCheck->listTables();
            System::getContainer()->get('monolog.logger.contao.general')->info('CourseProgressController: Tables in DB: ' . implode(', ', $tables));
        } catch (Exception $e) {
            System::getContainer()->get('monolog.logger.contao.general')->error('CourseProgressController: DB Check failed: ' . $e->getMessage());
        }

        $template->element_html_id = 'mod_' . $model->id;
        $template->element_css_classes = trim('mod_' . $model->type . ' ' . ($model->cssID[1] ?? ''));
        $template->class = $template->element_css_classes;
        $template->cssID = $model->cssID[0] ?? '';

        // Headline korrekt aufbereiten
        $headline = StringUtil::deserialize($model->headline);
        if (is_array($headline) && isset($headline['value']) && $headline['value'] !== '') {
            $template->headline = [
                'text' => $headline['value'],
                'unit' => $headline['unit'] ?? 'h1'
            ];
        } else {
            $template->headline = null;
        }

        if (!$user instanceof FrontendUser) {
            $template->isLoggedIn = false;
            return $template->getResponse();
        }

        $template->isLoggedIn = true;

        $assignmentId = (int)Input::get('assignment');
        if (!$assignmentId) {
            $assignmentId = (int)Input::get('auto_item');
        }

        System::getContainer()->get('monolog.logger.contao.general')->info('CourseProgressController: assignmentId determined as ' . $assignmentId . ' from Request URI ' . $request->getUri());

        if (!$assignmentId) {
            $template->notFound = true;
            return $template->getResponse();
        }

        $db = Database::getInstance();

        // Debug: Rohe Zuweisung prüfen
        $rawAssignment = $db->prepare("SELECT * FROM tl_dc_course_students WHERE id=?")->execute($assignmentId);
        if ($rawAssignment->numRows < 1) {
            System::getContainer()->get('monolog.logger.contao.general')->error('CourseProgressController: Assignment ID ' . $assignmentId . ' not found in tl_dc_course_students');
        } else {
            System::getContainer()->get('monolog.logger.contao.general')->info('CourseProgressController: Raw Assignment found. PID (Student): ' . $rawAssignment->pid . ', Course ID: ' . $rawAssignment->course_id . ', Event ID: ' . $rawAssignment->event_id);

            $rawStudent = $db->prepare("SELECT * FROM tl_dc_students WHERE id=?")->execute($rawAssignment->pid);
            if ($rawStudent->numRows < 1) {
                System::getContainer()->get('monolog.logger.contao.general')->error('CourseProgressController: Student ID ' . $rawAssignment->pid . ' not found for assignment ' . $assignmentId);
            } else {
                System::getContainer()->get('monolog.logger.contao.general')->info('CourseProgressController: Student found. MemberId: ' . $rawStudent->memberId . ', Email: ' . $rawStudent->email . ' (User Email: ' . $user->email . ', User ID: ' . $user->id . ')');
            }
        }

        // 1. Zuweisung laden und prüfen, ob sie dem User gehört
        $assignment = $db->prepare(
            'SELECT cs.*, c.title AS course_title, c.id AS course_id
             FROM tl_dc_course_students cs
             LEFT JOIN tl_dc_dive_course c ON c.id = cs.course_id
             LEFT JOIN tl_dc_students s ON s.id = cs.pid
             WHERE cs.id = ? AND (s.memberId = ? OR s.email = ?)'
        )->execute($assignmentId, (int)$user->id, (string)$user->email);

        System::getContainer()->get('monolog.logger.contao.general')->info('CourseProgressController: Found ' . $assignment->numRows . ' assignments for ID ' . $assignmentId . ' and User ID ' . $user->id);

        // Falls keine direkte Zuweisung über memberId oder Email gefunden wurde,
        // prüfen wir, ob der Student existiert und ggf. jetzt verknüpft werden kann.
        if ($assignment->numRows < 1) {
            // Debug & Auto-Repair:
            $checkAny = $db->prepare('SELECT cs.id, cs.pid FROM tl_dc_course_students cs WHERE cs.id=?')->execute($assignmentId);
            if ($checkAny->numRows > 0) {
                $studentId = (int)$checkAny->pid;
                $checkStudent = $db->prepare('SELECT id, memberId, email FROM tl_dc_students WHERE id=?')->execute($studentId);

                if ($checkStudent->numRows > 0) {
                    // Falls die E-Mail übereinstimmt, aber die memberId fehlt, reparieren wir das hier on-the-fly
                    if (!$checkStudent->memberId && $checkStudent->email === $user->email) {
                        $db->prepare('UPDATE tl_dc_students SET memberId=? WHERE id=?')->execute((int)$user->id, $studentId);

                        // Abfrage erneut ausführen
                        $assignment = $db->prepare(
                            'SELECT cs.*, c.title AS course_title, c.id AS course_id
                             FROM tl_dc_course_students cs
                             LEFT JOIN tl_dc_dive_course c ON c.id = cs.course_id
                             LEFT JOIN tl_dc_students s ON s.id = cs.pid
                             WHERE cs.id = ? AND s.memberId = ?'
                        )->execute($assignmentId, (int)$user->id);
                    }
                }
            }
        }

        if ($assignment->numRows < 1) {
            $template->notFound = true;
            // Falls notFound, geben wir trotzdem einen Hinweis aus
            System::getContainer()->get('monolog.logger.contao.general')->warning('CourseProgressController: Access denied or not found for assignment ' . $assignmentId . ' and User ' . $user->id);
            return $template->getResponse();
        }

        System::getContainer()->get('monolog.logger.contao.general')->info('CourseProgressController: Data loaded successfully for assignment ' . $assignmentId);

        $template->assignment = [
            'id' => (int)$assignment->id,
            'status' => (string)$assignment->status,
            'course_title' => (string)$assignment->course_title,
        ];

        // 2. Übungen laden
        $exercises = $db->prepare(
            'SELECT se.*, e.title AS exercise_title, m.title AS module_title
             FROM tl_dc_student_exercises se
             LEFT JOIN tl_dc_course_exercises e ON e.id = se.exercise_id
             LEFT JOIN tl_dc_course_modules m ON m.id = e.pid
             WHERE se.pid = ?
             ORDER BY m.sorting, e.sorting'
        )->execute($assignmentId);

        $exerciseList = [];
        while ($exercises->next()) {
            $exerciseList[] = [
                'id' => (int)$exercises->id,
                'title' => (string)$exercises->exercise_title,
                'module' => (string)$exercises->module_title,
                'status' => (string)$exercises->status,
                'exercise_id' => (int)$exercises->exercise_id,
            ];
        }
        $template->exercises = $exerciseList;

        // 3. Zeitplan laden (über das Event der Zuweisung)
        $schedule = [];
        if ($assignment->event_id) {
            $dateFormat = Config::get('datimFormat');
            $rows = $db->prepare(
                'SELECT s.*, m.title AS module_title, e.title AS exercise_title
                 FROM tl_dc_course_event_schedule s
                 LEFT JOIN tl_dc_course_modules m ON m.id = s.module_id
                 LEFT JOIN tl_dc_course_exercises e ON e.id = s.exercise_id
                 WHERE s.pid = ? AND s.published = 1
                 ORDER BY s.planned_at'
            )->execute((int)$assignment->event_id);

            while ($rows->next()) {
                $schedule[] = [
                    'planned_at' => $rows->planned_at ? Date::parse($dateFormat, (int)strtotime((string)$rows->planned_at)) : '',
                    'location' => (string)$rows->location,
                    'notes' => (string)$rows->notes,
                    'module' => (string)$rows->module_title,
                    'exercise' => (string)$rows->exercise_title,
                    'exercise_id' => (int)$rows->exercise_id,
                ];
            }
        }
        $template->schedule = $schedule;

        System::getContainer()->get('monolog.logger.contao.general')->info('CourseProgressController: Template variables set. Exercises: ' . count($exerciseList) . ', Schedule: ' . count($schedule));

        // Labels
        $template->labels = [
            'headline' => 'Kursfortschritt: ' . $assignment->course_title,
            'exercises' => 'Übersicht Übungen',
            'schedule' => 'Zeitplan / Termine',
            'status_pending' => 'Offen',
            'status_completed' => 'Absolviert',
            'status_failed' => 'Nicht erreicht',
        ];

        return $template->getResponse();
    }
}
