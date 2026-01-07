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
    public function __construct()
    {
        error_log('DEBUG: CourseProgressController::__construct');
        // Wir nutzen hier System::getContainer() da wir noch nicht wissen ob DI funktioniert
        try {
            System::getContainer()->get('monolog.logger.contao.general')->info('CourseProgressController::__construct called.');
        } catch (Exception $e) {
            // ignore
        }
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        error_log('DEBUG: CourseProgressController::getResponse called for URI ' . $request->getUri());
        $logger = System::getContainer()->get('monolog.logger.contao.general');
        $logger->info('CourseProgressController::getResponse start. REQUEST_URI: ' . $request->getUri());

        $user = System::getContainer()->get('security.helper')->getUser();
        $logger->info('CourseProgressController: User ID: ' . ($user ? $user->id : 'none'));

        // Debug: Tabellen prüfen
        try {
            $dbCheck = Database::getInstance();
            $tables = $dbCheck->listTables();
            $logger->info('CourseProgressController: Tables in DB count: ' . count($tables));
        } catch (Exception $e) {
            $logger->error('CourseProgressController: DB Check failed: ' . $e->getMessage());
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
        }

        if (!$user instanceof FrontendUser) {
            $template->isLoggedIn = false;
            return $template->getResponse();
        }

        $template->isLoggedIn = true;

        $assignmentId = (int)$request->query->get('assignment');
        if (!$assignmentId) {
            $assignmentId = (int)$request->attributes->get('auto_item');
        }

        // Fallback falls auto_item im Request-Attribut nicht da ist, aber in der URL vorkommen könnte (Legacy)
        if (!$assignmentId) {
            $assignmentId = (int)Input::get('assignment') ?: (int)Input::get('auto_item');
        }

        $logger->info('CourseProgressController: Params check. assignment (query): ' . $request->query->get('assignment') . ', auto_item (attr): ' . $request->attributes->get('auto_item') . ', final ID: ' . $assignmentId);

        if (!$assignmentId) {
            $logger->warning('CourseProgressController: No assignment ID found in request.');
            $template->notFound = true;
            return $template->getResponse();
        }

        $db = Database::getInstance();

        // 1. Zuweisung laden und prüfen, ob sie dem User gehört
        // Wir suchen zuerst die Zuweisung, um zu sehen ob sie überhaupt existiert.
        $assignmentResult = $db->prepare(
            'SELECT cs.*, c.title AS course_title, c.id AS course_id, s.memberId, s.email as student_email
             FROM tl_dc_course_students cs
             LEFT JOIN tl_dc_dive_course c ON c.id = cs.course_id
             LEFT JOIN tl_dc_students s ON s.id = cs.pid
             WHERE cs.id = ?'
        )->execute($assignmentId);

        $logger->info('CourseProgressController: SQL Executed for ID ' . $assignmentId . '. Rows: ' . $assignmentResult->numRows);

        $logger->info('CourseProgressController: No of Assignments found: ' . $assignmentResult->numRows);

        if ($assignmentResult->numRows < 1) {
            $logger->error('CourseProgressController: Assignment ID ' . $assignmentId . ' not found in database.');
            $template->notFound = true;
            return $template->getResponse();
        }

        $assignmentRow = $assignmentResult->fetchAssoc();
        $logger->info('CourseProgressController: Assignment found. Student PID: ' . $assignmentRow['pid'] . ', MemberId: ' . $assignmentRow['memberId'] . ', Email: ' . $assignmentRow['student_email']);

        // Falls course_id fehlt, versuchen wir sie aus dem Event zu holen
        if (!(int)$assignmentRow['course_id'] && (int)$assignmentRow['event_id']) {
            $eventResult = $db->prepare("SELECT course_id FROM tl_dc_course_event WHERE id=?")->execute((int)$assignmentRow['event_id']);
            if ($eventResult->numRows > 0) {
                $assignmentRow['course_id'] = $eventResult->course_id;
                $db->prepare('UPDATE tl_dc_course_students SET course_id=? WHERE id=?')->execute($assignmentRow['course_id'], $assignmentId);
                $logger->info('CourseProgressController: Fixed missing course_id from event. New Course ID: ' . $assignmentRow['course_id']);

                // Titel auch aktualisieren für die Anzeige
                $courseTitleResult = $db->prepare("SELECT title FROM tl_dc_dive_course WHERE id=?")->execute($assignmentRow['course_id']);
                if ($courseTitleResult->numRows > 0) {
                    $assignmentRow['course_title'] = $courseTitleResult->title;
                }
            } else {
                $logger->warning('CourseProgressController: Event ID ' . $assignmentRow['event_id'] . ' not found for fixing course_id.');
            }
        }

        // Berechtigungsprüfung
        $hasAccess = ((int)$assignmentRow['memberId'] === (int)$user->id) || ($assignmentRow['student_email'] === $user->email);
        $logger->info('CourseProgressController: Access check. memberId match: ' . ((int)$assignmentRow['memberId'] === (int)$user->id ? 'YES' : 'NO') . ', email match: ' . ($assignmentRow['student_email'] === $user->email ? 'YES' : 'NO'));

        if (!$hasAccess && (int)$assignmentRow['memberId'] === 0 && $assignmentRow['student_email'] === $user->email) {
            $logger->info('CourseProgressController: Auto-Repairing memberId for student ID ' . $assignmentRow['pid']);
            $db->prepare('UPDATE tl_dc_students SET memberId=? WHERE id=?')->execute((int)$user->id, $assignmentRow['pid']);
            $hasAccess = true;
        }

        if (!$hasAccess) {
            $logger->warning('CourseProgressController: Access denied for User ' . $user->id . ' to Assignment ' . $assignmentId);
            $template->notFound = true;
            return $template->getResponse();
        }

        $template->assignment = [
            'id' => (int)$assignmentRow['id'],
            'status' => (string)$assignmentRow['status'],
            'course_title' => (string)$assignmentRow['course_title'],
        ];

        $logger->info('CourseProgressController: Loading exercises for PID ' . $assignmentId);
        $exercises = $db->prepare(
            'SELECT se.*, e.title AS exercise_title, m.title AS module_title
             FROM tl_dc_student_exercises se
             LEFT JOIN tl_dc_course_exercises e ON e.id = se.exercise_id
             LEFT JOIN tl_dc_course_modules m ON m.id = e.pid
             WHERE se.pid = ?
             ORDER BY m.sorting, e.sorting'
        )->execute($assignmentId);

        // WICHTIG: Lade die Sprachdateien explizit für das Frontend
        System::loadLanguageFile('tl_dc_student_exercises');

        $exerciseList = [];
        while ($exercises->next()) {
            $exerciseList[] = [
                'id' => (int)$exercises->id,
                'title' => (string)$exercises->exercise_title,
                'module' => (string)$exercises->module_title,
                'status' => (string)$exercises->status,
                'status_label' => $GLOBALS['TL_LANG']['tl_dc_student_exercises']['itemStatus'][(string)$exercises->status]
                    ?? (string)$exercises->status,
                'exercise_id' => (int)$exercises->exercise_id,
            ];
        }
        $template->exercises = $exerciseList;
        $logger->info('CourseProgressController: Loaded ' . count($exerciseList) . ' exercises.');

        // Falls wir im Logger sehen wollen, was genau geladen wurde:
        if (count($exerciseList) > 0) {
            $logger->info('CourseProgressController: First exercise: ' . $exerciseList[0]['title'] . ' (Status: ' . $exerciseList[0]['status'] . ')');
        }

        // Wenn keine Übungen da sind, versuchen wir sie zu generieren (falls ein Kurs verknüpft ist)
        if (empty($exerciseList) && (int)$assignmentRow['course_id'] > 0) {
            $logger->info('CourseProgressController: Exercises empty, trying to generate for assignment ' . $assignmentId);

            $db_exercises = $db->prepare("
                SELECT e.id
                FROM tl_dc_course_exercises e
                JOIN tl_dc_course_modules m ON e.pid = m.id
                WHERE m.pid = ?
            ")->execute((int)$assignmentRow['course_id']);

            while ($db_exercises->next()) {
                $check = $db->prepare("SELECT id FROM tl_dc_student_exercises WHERE pid=? AND exercise_id=?")->execute($assignmentId, $db_exercises->id);
                if ($check->numRows < 1) {
                    $db->prepare("INSERT INTO tl_dc_student_exercises (pid, tstamp, exercise_id, status, published) VALUES (?, ?, ?, ?, ?)")
                        ->execute($assignmentId, time(), $db_exercises->id, 'pending', 1);
                }
            }

            // Erneut laden
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
                    'status_label' => $GLOBALS['TL_LANG']['tl_dc_student_exercises']['itemStatus'][(string)$exercises->status]
                        ?? (string)$exercises->status,
                    'exercise_id' => (int)$exercises->exercise_id,
                ];
            }
            $template->exercises = $exerciseList;
            $logger->info('CourseProgressController: After generation loaded ' . count($exerciseList) . ' exercises.');
        }

        // 3. Zeitplan laden (über das Event der Zuweisung)
        $schedule = [];
        if ($assignmentRow['event_id']) {
            $dateFormat = Config::get('datimFormat');
            $rows = $db->prepare(
                'SELECT s.*, m.title AS module_title, e.title AS exercise_title
                 FROM tl_dc_course_event_schedule s
                 LEFT JOIN tl_dc_course_modules m ON m.id = s.module_id
                 LEFT JOIN tl_dc_course_exercises e ON e.id = s.exercise_id
                 WHERE s.pid = ? AND s.published = 1
                 ORDER BY s.planned_at'
            )->execute((int)$assignmentRow['event_id']);

            while ($rows->next()) {
                $schedule[] = [
                    'planned_at' => $rows->planned_at ? Date::parse($dateFormat, (int)$rows->planned_at) : '',
                    'location' => (string)$rows->location,
                    'notes' => (string)$rows->notes,
                    'module' => (string)$rows->module_title,
                    'exercise' => (string)$rows->exercise_title,
                    'exercise_id' => (int)$rows->exercise_id,
                ];
            }
        }
        $template->schedule = $schedule;
        $logger->info('CourseProgressController: Loaded ' . count($schedule) . ' schedule entries.');

        // Labels
        $template->labels = [
            'headline' => 'Kursfortschritt: ' . ($assignmentRow['course_title'] ?: 'Kurs'),
            'exercises' => 'Übersicht Übungen',
            'schedule' => 'Zeitplan / Termine',
        ];

        return $template->getResponse();
    }
}
