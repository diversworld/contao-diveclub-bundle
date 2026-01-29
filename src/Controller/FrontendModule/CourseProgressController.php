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

        $dateFormat = Config::get('datimFormat');

        // Berechtigungsprüfung (Instructor-Check)
        $isInstructor = false;
        if ($user instanceof FrontendUser) {
            $groups = StringUtil::deserialize($user->groups, true);

            // Konfiguration laden
            $db = Database::getInstance();
            $configResult = $db->prepare("SELECT instructor_groups FROM tl_dc_config WHERE published='1' LIMIT 1")->execute();

            $instructorGroups = [];
            if ($configResult->numRows > 0) {
                $instructorGroups = StringUtil::deserialize($configResult->instructor_groups, true);
            }

            // Fallback auf Standardgruppen, falls nichts konfiguriert ist
            if (empty($instructorGroups)) {
                $instructorGroups = ['2', '3'];
            }

            foreach ($instructorGroups as $groupId) {
                if (in_array((string)$groupId, $groups, true)) {
                    $isInstructor = true;
                    break;
                }
            }
        }

        // AJAX-Request für Status-Update verarbeiten
        if ($request->isXmlHttpRequest() && $request->request->get('action') === 'toggleExerciseStatus') {
            $exerciseId = (int)$request->request->get('exerciseId');
            $newStatus = $request->request->get('status') === 'completed' ? 'completed' : 'pending';

            if ($isInstructor && $exerciseId > 0) {
                $db = Database::getInstance();
                $db->prepare("UPDATE tl_dc_student_exercises SET status=?, dateCompleted=? WHERE id=?")
                    ->execute($newStatus, $newStatus === 'completed' ? time() : '', $exerciseId);

                return new Response(json_encode(['success' => true, 'newStatus' => $newStatus]));
            }

            return new Response(json_encode(['success' => false, 'error' => 'Access denied']), 403);
        }

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
        $template->notFound = false;
        $template->hasProgress = false;
        $template->studentProgress = [];
        $template->exercises = [];
        $template->schedule = [];
        $template->labels = [];

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
        $template->isInstructor = $isInstructor;

        // 2. Alle Module des Kurses laden (um sicherzustellen, dass alle Module angezeigt werden)
        $courseId = (int)$assignmentRow['course_id'];
        $modulesResult = $db->prepare('SELECT id, title FROM tl_dc_course_modules WHERE pid = ? ORDER BY sorting')
            ->execute($courseId);

        $modules = [];
        while ($modulesResult->next()) {
            $modules[$modulesResult->id] = [
                'title' => (string)$modulesResult->title,
                'exercises' => []
            ];
        }

        try {
            $exercises = $db->prepare(
                'SELECT se.*, e.title AS exercise_title, m.title AS module_title
                 FROM tl_dc_student_exercises se
                 LEFT JOIN tl_dc_course_exercises e ON e.id = se.exercise_id
                 LEFT JOIN tl_dc_course_modules m ON m.id = se.module_id
                 WHERE se.pid = ?
                 ORDER BY se.sorting'
            )->execute($assignmentId);
        } catch (\Exception $e) {
            $exercises = $db->prepare(
                'SELECT se.*, e.title AS exercise_title, m.title AS module_title
                 FROM tl_dc_student_exercises se
                 LEFT JOIN tl_dc_course_exercises e ON e.id = se.exercise_id
                 LEFT JOIN tl_dc_course_modules m ON m.id = e.pid
                 WHERE se.pid = ?
                 ORDER BY se.sorting'
            )->execute($assignmentId);
        }

        // WICHTIG: Lade die Sprachdateien explizit für das Frontend
        System::loadLanguageFile('tl_dc_student_exercises');

        $exerciseList = [];
        while ($exercises->next()) {
            $title = (string)$exercises->exercise_title;
            if ($exercises->exercise_id == 0) {
                $title = 'Modul-Abschluss';
            }

            $exData = [
                'id' => (int)$exercises->id,
                'title' => $title,
                'module' => (string)$exercises->module_title,
                'status' => (string)$exercises->status,
                'instructor' => (string)$exercises->instructor,
                'dateCompleted' => $exercises->dateCompleted ? Date::parse($dateFormat, (int)$exercises->dateCompleted) : '',
                'status_label' => $GLOBALS['TL_LANG']['tl_dc_student_exercises']['itemStatus'][(string)$exercises->status]
                    ?? (string)$exercises->status,
                'exercise_id' => (int)$exercises->exercise_id,
                'module_id' => (int)($exercises->module_id ?: 0),
            ];
            $exerciseList[] = $exData;

            $mId = (int)($exercises->module_id ?: 0);
            if (isset($modules[$mId])) {
                $modules[$mId]['exercises'][] = $exData;
            }
        }
        $template->exercises = $exerciseList;
        $template->modules = $modules;
        $logger->info('CourseProgressController: Loaded ' . count($exerciseList) . ' exercises in ' . count($modules) . ' modules.');

        // Falls wir im Logger sehen wollen, was genau geladen wurde:
        if (count($exerciseList) > 0) {
            $logger->info('CourseProgressController: First exercise: ' . $exerciseList[0]['title'] . ' (Status: ' . $exerciseList[0]['status'] . ')');
        }

        // Wenn keine Übungen da sind, versuchen wir sie zu generieren (falls ein Kurs verknüpft ist)
        if (empty($exerciseList) && (int)$assignmentRow['course_id'] > 0) {
            $db_exercises = $db->prepare("
                    SELECT e.id, m.id AS module_id
                    FROM tl_dc_course_exercises e
                    JOIN tl_dc_course_modules m ON e.pid = m.id
                    WHERE m.pid = ?
                ")->execute((int)$assignmentRow['course_id']);

            $hasModuleId = true;
            try {
                $db->prepare("SELECT module_id FROM tl_dc_student_exercises LIMIT 1")->execute();
            } catch (\Exception $e) {
                $hasModuleId = false;
            }

            while ($db_exercises->next()) {
                $check = $db->prepare("SELECT id FROM tl_dc_student_exercises WHERE pid=? AND exercise_id=?")->execute($assignmentId, $db_exercises->id);
                if ($check->numRows < 1) {
                    $plannedAt = '';
                    $instructor = '';

                    // Datum und Instruktor vom Modul aus dem Event-Zeitplan erben
                    if ((int)$assignmentRow['event_id'] > 0) {
                        // Zuerst im Zeitplan nach spezifischer Übung suchen
                        $objScheduleEx = $db->prepare("
                                SELECT se.planned_at, se.instructor
                                FROM tl_dc_event_schedule_exercises se
                                JOIN tl_dc_course_event_schedule s ON s.id = se.pid
                                WHERE s.pid = ? AND se.exercise_id = ?
                                LIMIT 1
                            ")->execute((int)$assignmentRow['event_id'], $db_exercises->id);

                        if ($objScheduleEx->numRows > 0) {
                            $plannedAt = $objScheduleEx->planned_at;
                            $instructor = $objScheduleEx->instructor;
                        }

                        // Falls nicht gefunden oder leer, vom Modul-Zeitplan erben
                        if ($plannedAt === '') {
                            $objSchedule = $db->prepare("SELECT planned_at, instructor FROM tl_dc_course_event_schedule WHERE pid=? AND module_id=? LIMIT 1")
                                ->execute((int)$assignmentRow['event_id'], $db_exercises->module_id);

                            if ($objSchedule->numRows > 0) {
                                $plannedAt = $objSchedule->planned_at;
                                if ($instructor === '') {
                                    $instructor = $objSchedule->instructor;
                                }
                            }
                        }
                    }

                    if ($hasModuleId) {
                        $db->prepare("INSERT INTO tl_dc_student_exercises (pid, tstamp, exercise_id, module_id, status, dateCompleted, instructor, published) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                            ->execute($assignmentId, time(), $db_exercises->id, $db_exercises->module_id, 'pending', $plannedAt, $instructor, 1);
                    } else {
                        $db->prepare("INSERT INTO tl_dc_student_exercises (pid, tstamp, exercise_id, status, dateCompleted, instructor, published) VALUES (?, ?, ?, ?, ?, ?, ?)")
                            ->execute($assignmentId, time(), $db_exercises->id, 'pending', $plannedAt, $instructor, 1);
                    }
                }
            }

            // Erneut laden
            try {
                $exercises = $db->prepare(
                    'SELECT se.*, e.title AS exercise_title, m.title AS module_title
                     FROM tl_dc_student_exercises se
                     LEFT JOIN tl_dc_course_exercises e ON e.id = se.exercise_id
                     LEFT JOIN tl_dc_course_modules m ON m.id = se.module_id
                     WHERE se.pid = ?
                     ORDER BY se.sorting'
                )->execute($assignmentId);
            } catch (\Exception $e) {
                $exercises = $db->prepare(
                    'SELECT se.*, e.title AS exercise_title, m.title AS module_title
                     FROM tl_dc_student_exercises se
                     LEFT JOIN tl_dc_course_exercises e ON e.id = se.exercise_id
                     LEFT JOIN tl_dc_course_modules m ON m.id = e.pid
                     WHERE se.pid = ?
                     ORDER BY se.sorting'
                )->execute($assignmentId);
            }

            $exerciseList = [];
            while ($exercises->next()) {
                $title = (string)$exercises->exercise_title;
                if ($exercises->exercise_id == 0) {
                    $title = 'Modul-Abschluss';
                }

                $exData = [
                    'id' => (int)$exercises->id,
                    'title' => $title,
                    'module' => (string)$exercises->module_title,
                    'instructor' => (string)$exercises->instructor,
                    'dateCompleted' => $exercises->dateCompleted ? Date::parse($dateFormat, (int)$exercises->dateCompleted) : '',
                    'status' => (string)$exercises->status,
                    'status_label' => $GLOBALS['TL_LANG']['tl_dc_student_exercises']['itemStatus'][(string)$exercises->status]
                        ?? (string)$exercises->status,
                    'exercise_id' => (int)$exercises->exercise_id,
                    'module_id' => (int)($exercises->module_id ?: 0),
                    'notes' => (string)$exercises->notes,
                    'planned_at' => $exercises->dateCompleted ? Date::parse($dateFormat, (int)$exercises->dateCompleted) : '',
                ];
                $exerciseList[] = $exData;

                $mId = (int)($exercises->module_id ?: 0);
                if (isset($modules[$mId])) {
                    $modules[$mId]['exercises'][] = $exData;
                }
            }
            $template->exercises = $exerciseList;
            $template->modules = $modules;
        }

        // 3. Zeitplan laden (über das Event der Zuweisung)
        $schedule = [];
        if ($assignmentRow['event_id']) {
            $rows = $db->prepare(
                'SELECT s.*, m.title AS module_title
                 FROM tl_dc_course_event_schedule s
                 LEFT JOIN tl_dc_course_modules m ON m.id = s.module_id
                 WHERE s.pid = ? AND s.published = "1"
                 ORDER BY s.planned_at'
            )->execute((int)$assignmentRow['event_id']);

            while ($rows->next()) {
                // Übungen für diesen Zeitplan-Eintrag holen
                $exerciseRows = $db->prepare('SELECT title FROM tl_dc_event_schedule_exercises WHERE pid = ? AND published = "1" ORDER BY sorting')
                    ->execute($rows->id);
                $exerciseTitles = $exerciseRows->fetchEach('title');

                $schedule[] = [
                    'planned_at' => $rows->planned_at ? Date::parse($dateFormat, (int)$rows->planned_at) : '',
                    'location' => (string)$rows->location,
                    'notes' => (string)$rows->notes,
                    'instructor' => (string)$rows->instructor,
                    'module' => (string)$rows->module_title,
                    'exercise' => implode(', ', $exerciseTitles),
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
