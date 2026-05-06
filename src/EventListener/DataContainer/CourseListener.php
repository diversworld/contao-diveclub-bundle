<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Contao\Date;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Doctrine\DBAL\Connection;

class CourseListener
{
    use AliasHandlerTrait;

    public function __construct(
        private readonly Connection        $connection,
        private readonly Slug              $slug,
        private readonly DcaTemplateHelper $templateHelper
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
            $args[1] = $args[1] ? Date::parse(Config::get('datimFormat'), (int)$args[1]) : 'kein Datum';
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

    #[AsCallback(table: 'tl_dc_course_modules', target: 'fields.course_id.options')]
    public function onModuleCourseOptions(): array
    {
        $rows = $this->connection->fetchAllAssociative("SELECT id, title FROM tl_dc_dive_course ORDER BY title LIMIT 500");
        $options = [];
        foreach ($rows as $row) {
            $options[$row['id']] = $row['title'];
        }
        return $options;
    }

    #[AsCallback(table: 'tl_dc_course_exercises', target: 'fields.module_id.options')]
    public function onExerciseModuleOptions(): array
    {
        $rows = $this->connection->fetchAllAssociative("SELECT id, title FROM tl_dc_course_modules ORDER BY title LIMIT 500");
        $options = [];
        foreach ($rows as $row) {
            $options[$row['id']] = $row['title'];
        }
        return $options;
    }
}
