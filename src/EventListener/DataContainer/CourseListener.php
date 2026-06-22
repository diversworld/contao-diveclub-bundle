<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Contao\Date;
use Contao\Image;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Diversworld\ContaoDiveclubBundle\NotificationType\CourseScheduleUpdateNotificationType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Csrf\CsrfToken;
use Terminal42\NotificationCenterBundle\NotificationCenter;

class CourseListener
{
    use AliasHandlerTrait;

    public function __construct(
        private readonly Connection         $connection,
        private readonly Slug               $slug,
        private readonly DcaTemplateHelper  $templateHelper,
        private readonly NotificationCenter $notificationCenter
    )
    {
    }

    /* tl_dc_dive_course */

    #[AsCallback(table: 'tl_dc_dive_course', target: 'fields.alias.save')]
    public function onDiveCourseAliasSave(mixed $varValue, DataContainer $dc): mixed
    {
        return $this->generateAliasWithValidation($this->connection, $this->slug, $varValue, $dc, 'tl_dc_dive_course');
    }

    #[AsCallback(table: 'tl_dc_dive_course', target: 'fields.course_type.options')]
    public function onDiveCourseTypeOptions(): array
    {
        return $this->templateHelper->getCourseTypes();
    }

    #[AsCallback(table: 'tl_dc_dive_course', target: 'fields.category.options')]
    public function onDiveCourseCategoryOptions(): array
    {
        return $this->templateHelper->getCourseCategories();
    }

    /* tl_dc_course_event */

    #[AsCallback(table: 'tl_dc_course_event', target: 'list.label.label')]
    public function onCourseEventLabel(array $row, string $label, DataContainer $dc, ?array $args = null): array|string
    {
        if (null !== $args) {
            //$args[1] = $args[1] ? Date::parse(Config::get('datimFormat'), (int)$args[1]) : 'kein Datum';
            return $args;
        }

        $date = $row['dateStart'] ? Date::parse(Config::get('datimFormat'), (int)$row['dateStart']) : 'kein Datum';
        return sprintf('%s <span style="color:#999;">(%s)</span>', $row['title'], $date);
    }

    #[AsCallback(table: 'tl_dc_course_event', target: 'config.onsubmit')]
    public function onCourseEventSubmit(DataContainer $dc): void
    {
        if (!$dc->activeRecord || !$dc->activeRecord->course_id) {
            return;
        }

        $id = (int)($dc->id ?: $dc->activeRecord->id);
        $exists = $this->connection->fetchOne("SELECT id FROM tl_dc_course_event_schedule WHERE pid=? LIMIT 1", [$id]);

        if ($exists) {
            return;
        }

        $modules = $this->connection->fetchAllAssociative("
            SELECT m.id AS module_id FROM tl_dc_course_modules m WHERE m.pid = ? ORDER BY m.sorting
        ", [(int)$dc->activeRecord->course_id]);

        $sorting = 128;
        foreach ($modules as $module) {
            $this->connection->insert('tl_dc_course_event_schedule', [
                'pid' => $id,
                'tstamp' => time(),
                'sorting' => $sorting,
                'module_id' => (int)$module['module_id'],
                'published' => '1'
            ]);

            $scheduleId = (int)$this->connection->lastInsertId();
            $exercises = $this->connection->fetchAllAssociative("SELECT * FROM tl_dc_course_exercises WHERE pid=? ORDER BY sorting", [(int)$module['module_id']]);

            $exSorting = 128;
            foreach ($exercises as $exercise) {
                $this->connection->insert('tl_dc_event_schedule_exercises', [
                    'pid' => $scheduleId,
                    'tstamp' => time(),
                    'sorting' => $exSorting,
                    'exercise_id' => (int)$exercise['id'],
                    'title' => $exercise['title'],
                    'description' => $exercise['description'],
                    'required' => $exercise['required'],
                    'duration' => $exercise['duration'],
                    'notes' => $exercise['notes'],
                    'published' => '1'
                ]);
                $exSorting += 128;
            }
            $sorting += 128;
        }
    }

    /* tl_dc_course_event_schedule */

    #[AsCallback(table: 'tl_dc_course_event_schedule', target: 'list.label.label')]
    public function onScheduleLabel(array $row, string $label, DataContainer $dc, ?array $args = null): array|string
    {
        if (null !== $args) {
            if ($args[0]) {
                $args[0] = Date::parse(Config::get('datimFormat'), (int)$args[0]);
            }
            return $args;
        }

        $date = $row['planned_at'] ? Date::parse(Config::get('datimFormat'), (int)$row['planned_at']) : '-';
        return sprintf('%s — Modul: %s', $date, $label);
    }

    #[AsCallback(table: 'tl_dc_course_event_schedule', target: 'list.sorting.child_record')]
    public function onScheduleChildRecord(array $row): string
    {
        $date = $row['planned_at'] ? Date::parse(Config::get('datimFormat'), (int)$row['planned_at']) : '-';
        $module = $row['module_id'];
        if ($row['module_id'] > 0) {
            $moduleData = $this->connection->fetchAssociative("SELECT title FROM tl_dc_course_modules WHERE id = ?", [$row['module_id']]);
            if ($moduleData) {
                $module = $moduleData['title'];
            }
        }

        return sprintf('<div class="tl_content_left">%s — Modul: %s</div>', $date, $module);
    }

    #[AsCallback(table: 'tl_dc_course_event_schedule', target: 'config.onsubmit')]
    public function onScheduleSubmit(DataContainer $dc): void
    {
        if (!$dc->activeRecord) {
            return;
        }

        $scheduleId = (int)$dc->activeRecord->id;
        $eventId = (int)$dc->activeRecord->pid;
        $moduleId = (int)$dc->activeRecord->module_id;
        $plannedAt = $dc->activeRecord->planned_at;
        $instructor = $dc->activeRecord->instructor;

        if ($moduleId > 0) {
            $this->connection->executeStatement(
                "UPDATE tl_dc_event_schedule_exercises SET planned_at=?, instructor=? WHERE pid=? AND (planned_at=0 OR planned_at IS NULL)",
                [(int)$plannedAt, $instructor, $scheduleId]
            );

            $studentIds = $this->connection->fetchFirstColumn("SELECT id FROM tl_dc_course_students WHERE event_id=?", [$eventId]);

            if (!empty($studentIds)) {
                $scheduleEx = $this->connection->fetchAllAssociative(
                    "SELECT exercise_id, planned_at, instructor FROM tl_dc_event_schedule_exercises WHERE pid=? AND (published='1' OR published=1)",
                    [$scheduleId]
                );

                if (!empty($scheduleEx)) {
                    foreach ($scheduleEx as $objScheduleEx) {
                        $exPlannedAt = $objScheduleEx['planned_at'] ?: $plannedAt;
                        $exInstructor = $objScheduleEx['instructor'] ?: $instructor;
                        try {
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
                    $exIds = $this->connection->fetchFirstColumn("SELECT id FROM tl_dc_course_exercises WHERE pid=?", [$moduleId]);
                    if (!empty($exIds)) {
                        try {
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
        }

        // Generate default exercises if none exist
        if ($dc->activeRecord->module_id) {
            $exists = $this->connection->fetchOne("SELECT id FROM tl_dc_event_schedule_exercises WHERE pid=? LIMIT 1", [$scheduleId]);
            if (!$exists) {
                $exercises = $this->connection->fetchAllAssociative("SELECT * FROM tl_dc_course_exercises WHERE pid=? ORDER BY sorting", [$moduleId]);
                $sorting = 128;
                foreach ($exercises as $exercise) {
                    $this->connection->insert('tl_dc_event_schedule_exercises', [
                        'pid' => $scheduleId,
                        'tstamp' => time(),
                        'sorting' => $sorting,
                        'exercise_id' => (int)$exercise['id'],
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

    }

    #[AsCallback(table: 'tl_dc_course_event', target: 'config.onload')]
    public function onCourseEventLoad(DataContainer $dc): void
    {
        if (Input::get('key') !== 'notifyStudents' || !(int)Input::get('id')) {
            return;
        }

        $this->sendCourseEventScheduleNotification((int)Input::get('id'));
    }

    #[AsCallback(table: 'tl_dc_course_event', target: 'list.operations.notify_students.button')]
    public function showCourseEventNotificationButton(array $row, ?string $href, string $label, string $title, ?string $icon, string $attributes): string
    {
        $tokenManager = System::getContainer()->get('contao.csrf.token_manager');
        $tokenId = (string)System::getContainer()->getParameter('contao.csrf_token_name');
        $url = Backend::addToUrl(
            'id=' . (int)$row['id'] . '&key=notifyStudents&' . $tokenId . '=' . $tokenManager->getDefaultTokenValue(),
            true,
            [$tokenId]
        );

        return sprintf('<a href="%s" title="%s"%s>%s</a> ', $url, StringUtil::specialchars($title), $attributes, Image::getHtml((string)$icon, $label));
    }

    private function sendCourseEventScheduleNotification(int $eventId): void
    {
        $tokenManager = System::getContainer()->get('contao.csrf.token_manager');
        $tokenId = (string)System::getContainer()->getParameter('contao.csrf_token_name');
        $rt = (string)Input::get($tokenId) ?: (string)Input::get('rt');

        if ($rt === '' || !$tokenManager->isTokenValid(new CsrfToken($tokenId, $rt))) {
            throw new AccessDeniedException('Invalid request token.');
        }

        $event = $this->connection->fetchAssociative(
            "SELECT e.*, CONCAT(COALESCE(i.firstname, ''), ' ', COALESCE(i.lastname, '')) AS instructor_name
             FROM tl_dc_course_event e
             LEFT JOIN tl_member i ON i.id = e.instructor
             WHERE e.id = ?",
            [$eventId]
        );

        if (!$event) {
            Backend::redirect('contao');
        }

        $notificationId = $this->connection->fetchOne(
            "SELECT id FROM tl_nc_notification WHERE type = ? ORDER BY id LIMIT 1",
            [CourseScheduleUpdateNotificationType::NAME]
        );

        if (!$notificationId) {
            Message::addError(sprintf(
                $GLOBALS['TL_LANG']['tl_dc_course_event']['notify_missing_notification'] ?? 'Es wurde keine Notification-Center-Benachrichtigung vom Typ "%s" gefunden.',
                CourseScheduleUpdateNotificationType::NAME
            ));
            Backend::redirect(Backend::addToUrl('', true, ['key', 'rt', $tokenId]));
        }

        $scheduleRows = $this->fetchScheduleRows($eventId);

        if (empty($scheduleRows)) {
            Message::addInfo($GLOBALS['TL_LANG']['tl_dc_course_event']['notify_no_schedule'] ?? 'Für diese Kursveranstaltung sind derzeit keine Termine im Plan vorhanden.');
            Backend::redirect(Backend::addToUrl('', true, ['key', 'rt', $tokenId]));
        }

        $students = $this->connection->fetchAllAssociative(
            "SELECT DISTINCT s.firstname, s.lastname, s.email
             FROM tl_dc_course_students cs
             INNER JOIN tl_dc_students s ON s.id = cs.pid
             WHERE cs.event_id = ?
               AND cs.published = 1
               AND s.email <> ''
             ORDER BY s.lastname, s.firstname",
            [$eventId]
        );

        if (empty($students)) {
            Message::addInfo($GLOBALS['TL_LANG']['tl_dc_course_event']['notify_no_recipients'] ?? 'Es wurden keine Tauchschüler mit E-Mail-Adresse für diese Kursveranstaltung gefunden.');
            Backend::redirect(Backend::addToUrl('', true, ['key', 'rt', $tokenId]));
        }

        $currentSnapshot = $this->createScheduleSnapshot($scheduleRows);
        $previousSnapshot = $this->decodeScheduleSnapshot((string)($event['schedule_notification_snapshot'] ?? null));
        $changedRows = $this->calculateScheduleChanges($previousSnapshot, $currentSnapshot);

        if (empty($changedRows)) {
            Message::addInfo($GLOBALS['TL_LANG']['tl_dc_course_event']['notify_no_changes'] ?? 'Seit der letzten Benachrichtigung wurden keine Änderungen im Terminplan festgestellt.');
            Backend::redirect(Backend::addToUrl('', true, ['key', 'rt', $tokenId]));
        }

        $currentScheduleText = $this->formatScheduleText($scheduleRows);
        $currentScheduleHtml = nl2br(StringUtil::specialchars($currentScheduleText));
        $changedScheduleText = $this->formatScheduleChangesText($changedRows);
        $changedScheduleHtml = nl2br(StringUtil::specialchars($changedScheduleText));
        $primaryChange = $this->extractPrimaryChangeRow($changedRows);
        $sent = 0;

        foreach ($students as $student) {
            $email = trim((string)$student['email']);
            if ($email === '') {
                continue;
            }

            $studentName = trim((string)$student['firstname'] . ' ' . (string)$student['lastname']);
            $this->notificationCenter->sendNotification((int)$notificationId, [
                'student_email' => $email,
                'student_firstname' => (string)$student['firstname'],
                'student_lastname' => (string)$student['lastname'],
                'student_name' => $studentName,
                'event_title' => (string)($event['title'] ?? ''),
                'module_title' => (string)($primaryChange['module_title'] ?? ''),
                'planned_at' => !empty($primaryChange['planned_at']) ? Date::parse(Config::get('datimFormat'), (int)$primaryChange['planned_at']) : '',
                'location' => (string)($primaryChange['location'] ?? ''),
                'instructor_name' => trim((string)($primaryChange['instructor_name'] ?? $event['instructor_name'] ?? '')),
                'schedule_text' => $currentScheduleText,
                'schedule_html' => $currentScheduleHtml,
                'current_schedule_text' => $currentScheduleText,
                'current_schedule_html' => $currentScheduleHtml,
                'changed_schedule_text' => $changedScheduleText,
                'changed_schedule_html' => $changedScheduleHtml,
            ]);
            ++$sent;
        }

        $this->connection->update('tl_dc_course_event', [
            'schedule_notification_snapshot' => json_encode($currentSnapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'schedule_notification_sent_at' => time(),
            'tstamp' => time(),
        ], ['id' => $eventId]);

        Message::addConfirmation(sprintf(
            $GLOBALS['TL_LANG']['tl_dc_course_event']['notify_sent'] ?? 'Die Terminplanänderung mit %2$d geänderten Terminen wurde an %1$d Tauchschüler gesendet.',
            $sent,
            count($changedRows)
        ));
        Backend::redirect(Backend::addToUrl('', true, ['key', 'rt', $tokenId]));
    }

    private function fetchScheduleRows(int $eventId): array
    {
        return $this->connection->fetchAllAssociative(
            "SELECT s.id, s.planned_at, s.location, s.notes, s.published, m.title AS module_title,
                    CONCAT(COALESCE(i.firstname, ''), ' ', COALESCE(i.lastname, '')) AS instructor_name
             FROM tl_dc_course_event_schedule s
             LEFT JOIN tl_dc_course_modules m ON m.id = s.module_id
             LEFT JOIN tl_member i ON i.id = s.instructor
             WHERE s.pid = ?
             ORDER BY s.planned_at, s.sorting, s.id",
            [$eventId]
        );
    }

    private function decodeScheduleSnapshot(string $snapshot): array
    {
        if ('' === trim($snapshot)) {
            return [];
        }

        $decoded = json_decode($snapshot, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function createScheduleSnapshot(array $scheduleRows): array
    {
        $snapshot = [];

        foreach ($scheduleRows as $row) {
            $snapshot[(string)$row['id']] = $this->normalizeScheduleRow($row);
        }

        ksort($snapshot);

        return $snapshot;
    }

    private function normalizeScheduleRow(array $row): array
    {
        return [
            'id' => (int)($row['id'] ?? 0),
            'planned_at' => (int)($row['planned_at'] ?? 0),
            'module_title' => trim((string)($row['module_title'] ?? '')),
            'location' => trim((string)($row['location'] ?? '')),
            'instructor_name' => trim((string)($row['instructor_name'] ?? '')),
            'notes' => $this->normalizeTextValue((string)($row['notes'] ?? '')),
            'published' => !empty($row['published']) ? 1 : 0,
        ];
    }

    private function normalizeTextValue(string $value): string
    {
        $value = strip_tags(html_entity_decode($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $value = preg_replace('/\s+/u', ' ', $value);

        return trim((string)$value);
    }

    private function calculateScheduleChanges(array $previousSnapshot, array $currentSnapshot): array
    {
        $changes = [];

        foreach ($currentSnapshot as $id => $currentRow) {
            if (!isset($previousSnapshot[$id])) {
                $changes[] = ['type' => 'added', 'current' => $currentRow];
                continue;
            }

            if ($previousSnapshot[$id] !== $currentRow) {
                $changes[] = ['type' => 'updated', 'current' => $currentRow, 'previous' => $previousSnapshot[$id]];
            }
        }

        foreach ($previousSnapshot as $id => $previousRow) {
            if (!isset($currentSnapshot[$id])) {
                $changes[] = ['type' => 'removed', 'previous' => $previousRow];
            }
        }

        usort($changes, static function (array $left, array $right): int {
            $leftRow = $left['current'] ?? $left['previous'] ?? [];
            $rightRow = $right['current'] ?? $right['previous'] ?? [];

            return ((int)($leftRow['planned_at'] ?? 0) <=> (int)($rightRow['planned_at'] ?? 0))
                ?: ((int)($leftRow['id'] ?? 0) <=> (int)($rightRow['id'] ?? 0));
        });

        return $changes;
    }

    private function extractPrimaryChangeRow(array $changes): array
    {
        $firstChange = $changes[0] ?? [];

        return $firstChange['current'] ?? $firstChange['previous'] ?? [];
    }

    private function formatScheduleText(array $scheduleRows): string
    {
        $lines = [];

        foreach ($scheduleRows as $row) {
            $lines[] = $this->formatScheduleLine($this->normalizeScheduleRow($row));
        }

        return implode("\n", $lines);
    }

    private function formatScheduleChangesText(array $changes): string
    {
        $lines = [];

        foreach ($changes as $change) {
            $row = $change['current'] ?? $change['previous'] ?? [];
            $type = (string)($change['type'] ?? 'updated');
            $prefix = $GLOBALS['TL_LANG']['tl_dc_course_event']['notify_change_' . $type] ?? ucfirst($type);

            if ('updated' === $type && isset($change['previous'], $change['current'])) {
                $lines[] = sprintf(
                    '%s: %s -> %s',
                    $prefix,
                    $this->formatScheduleLine($change['previous']),
                    $this->formatScheduleLine($change['current'])
                );
                continue;
            }

            $lines[] = sprintf('%s: %s', $prefix, $this->formatScheduleLine($row));
        }

        return implode("\n", $lines);
    }

    private function formatScheduleLine(array $row): string
    {
        $date = !empty($row['planned_at']) ? Date::parse(Config::get('datimFormat'), (int)$row['planned_at']) : '-';
        $line = sprintf('%s - %s', $date, (string)($row['module_title'] ?? '-'));

        if ('' !== (string)($row['location'] ?? '')) {
            $line .= ' | ' . ($GLOBALS['TL_LANG']['tl_dc_course_event_schedule']['location'][0] ?? 'Location') . ': ' . $row['location'];
        }

        if ('' !== (string)($row['instructor_name'] ?? '')) {
            $line .= ' | ' . ($GLOBALS['TL_LANG']['tl_dc_course_event_schedule']['instructor'][0] ?? 'Instructor') . ': ' . $row['instructor_name'];
        }

        if ('' !== (string)($row['notes'] ?? '')) {
            $line .= ' | ' . ($GLOBALS['TL_LANG']['tl_dc_course_event_schedule']['notes'][0] ?? 'Notes') . ': ' . $row['notes'];
        }

        if (isset($row['published']) && !$row['published']) {
            $line .= ' | ' . ($GLOBALS['TL_LANG']['tl_dc_course_event_schedule']['published'][0] ?? 'Unpublished');
        }

        return $line;
    }

    #[AsCallback(table: 'tl_dc_course_exercises', target: 'fields.alias.save')]
    public function onExerciseAliasSave(mixed $varValue, DataContainer $dc): mixed
    {
        return $this->generateAliasWithValidation($this->connection, $this->slug, $varValue, $dc, 'tl_dc_course_exercises');
    }

    #[AsCallback(table: 'tl_dc_course_modules', target: 'fields.alias.save')]
    public function onModuleAliasSave(mixed $varValue, DataContainer $dc): mixed
    {
        return $this->generateAliasWithValidation($this->connection, $this->slug, $varValue, $dc, 'tl_dc_course_modules');
    }

    #[AsCallback(table: 'tl_dc_course_modules', target: 'fields.pid.options')]
    #[AsCallback(table: 'tl_dc_course_modules', target: 'fields.course_id.options')]
    #[AsCallback(table: 'tl_dc_course_students', target: 'fields.dive_course_id.options')]
    #[AsCallback(table: 'tl_dc_course_event', target: 'fields.dive_course_id.options')]
    public function onModuleCourseOptions(): array
    {
        $rows = $this->connection->fetchAllAssociative("SELECT id, title FROM tl_dc_dive_course ORDER BY title");
        $options = [];
        foreach ($rows as $row) {
            $options[$row['id']] = $row['title'];
        }
        return $options;
    }

    #[AsCallback(table: 'tl_dc_course_exercises', target: 'fields.pid.options')]
    #[AsCallback(table: 'tl_dc_course_exercises', target: 'fields.module_id.options')]
    #[AsCallback(table: 'tl_dc_course_event_schedule', target: 'fields.module_id.options')]
    #[AsCallback(table: 'tl_dc_student_exercises', target: 'fields.module_id.options')]
    public function onExerciseModuleOptions(): array
    {
        $rows = $this->connection->fetchAllAssociative("SELECT id, title FROM tl_dc_course_modules ORDER BY title");
        $options = [];
        foreach ($rows as $row) {
            $options[$row['id']] = $row['title'];
        }
        return $options;
    }

    #[AsCallback(table: 'tl_dc_course_event_schedule', target: 'fields.pid.options')]
    #[AsCallback(table: 'tl_dc_course_event_schedule', target: 'fields.event_id.options')]
    #[AsCallback(table: 'tl_dc_course_students', target: 'fields.event_id.options')]
    public function onEventOptions(): array
    {
        $rows = $this->connection->fetchAllAssociative("SELECT id, title FROM tl_dc_course_event ORDER BY title");
        $options = [];
        foreach ($rows as $row) {
            $options[$row['id']] = $row['title'];
        }
        return $options;
    }

    #[AsCallback(table: 'tl_dc_event_schedule_exercises', target: 'fields.pid.options')]
    public function onScheduleOptions(): array
    {
        $rows = $this->connection->fetchAllAssociative("SELECT id FROM tl_dc_course_event_schedule ORDER BY id DESC");
        $options = [];
        foreach ($rows as $row) {
            $options[$row['id']] = (string)$row['id'];
        }
        return $options;
    }

    #[AsCallback(table: 'tl_dc_event_schedule_exercises', target: 'fields.exercise_id.options')]
    #[AsCallback(table: 'tl_dc_student_exercises', target: 'fields.exercise_id.options')]
    public function onExerciseOptions(): array
    {
        $rows = $this->connection->fetchAllAssociative("SELECT id, title FROM tl_dc_course_exercises ORDER BY title");
        $options = [];
        foreach ($rows as $row) {
            $options[$row['id']] = $row['title'];
        }
        return $options;
    }

    #[AsCallback(table: 'tl_dc_reservation_items', target: 'fields.pid.options')]
    public function getReservationOptions(): array
    {
        $rows = $this->connection->fetchAllAssociative("SELECT id, title FROM tl_dc_reservation ORDER BY title DESC");
        $options = [];
        foreach ($rows as $row) {
            $options[$row['id']] = $row['title'];
        }
        return $options;
    }
}
