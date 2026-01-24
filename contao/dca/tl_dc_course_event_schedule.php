<?php

declare(strict_types=1);

/*
 * DCA: tl_dc_course_event_schedule
 * Zeitplan‑Einträge (geplante Übungen) pro Kursveranstaltung
 */

use Contao\Backend;
use Contao\Config;
use Contao\Database;
use Contao\DataContainer;
use Contao\Date;
use Contao\DC_Table;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\ScheduleLabelListener;

$GLOBALS['TL_DCA']['tl_dc_course_event_schedule'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_course_event',
        'enableVersioning' => true,
        'markAsCopy' => 'headline',
        'onsubmit_callback' => [
            ['tl_dc_course_event_schedule', 'syncToStudentExercises']
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'module_id' => 'index',
                'exercise_id' => 'index',
                'planned_at' => 'index',
                'published,start,stop' => 'index'
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['planned_at', 'module_id', 'exercise_id'],
            'headerFields' => ['title', 'dateStart', 'dateEnd'],
            'flag' => DataContainer::SORT_MONTH_ASC,
            'panelLayout' => 'sort,filter;search,limit',
            'disableGrouping' => true,
        ],
        'label' => [
            'fields' => ['planned_at', 'module_id', 'exercise_id'],
            'format' => '%s — Modul: %s — Übung: %s',
            'label_callback' => [ScheduleLabelListener::class, '__invoke']
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
        ],
        'operations' => [
            'edit',
            'copy',
            'cut',
            'delete',
            'toggle',
            'show',
        ],
    ],
    'palettes' => [
        'default' => '{plan_legend},module_id,exercise_id,planned_at,location,instructor;
                      {notes_legend},notes;
                      {publish_legend},published,start,stop'
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'pid' => [
            'foreignKey' => 'tl_dc_course_event.title',
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'sorting' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'module_id' => [
            'inputType' => 'select',
            'foreignKey' => 'tl_dc_course_modules.title',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'exercise_id' => [
            'inputType' => 'select',
            'foreignKey' => 'tl_dc_course_exercises.title',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'planned_at' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'location' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'instructor' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'notes' => [
            'inputType' => 'textarea',
            'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
        'published' => [
            'toggle' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50 clr'],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'start' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'clr w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''"
        ],
        'stop' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''"
        ],
    ],
];

class tl_dc_course_event_schedule extends Backend
{
    /**
     * Synchronisiert Änderungen am Zeitplan mit den Übungsergebnissen der Schüler
     */
    public function syncToStudentExercises(DataContainer $dc): void
    {
        if (!$dc->activeRecord) {
            return;
        }

        $db = Database::getInstance();
        $eventId = (int)$dc->activeRecord->pid;
        $exerciseId = (int)$dc->activeRecord->exercise_id;
        $instructor = $dc->activeRecord->instructor;

        // 1. Alle Zuweisungen für dieses Event finden
        $students = $db->prepare("SELECT id FROM tl_dc_course_students WHERE event_id=?")
            ->execute($eventId);

        if ($students->numRows < 1) {
            return;
        }

        $studentIds = $students->fetchEach('id');

        // 2. Alle Schüler-Übungen aktualisieren, die zu diesen Zuweisungen gehören und die gleiche Übungs-ID haben
        // Wir synchronisieren hier den Instructor.
        // Falls gewünscht, könnten wir auch andere Felder synchronisieren.
        $db->prepare("UPDATE tl_dc_student_exercises SET instructor=? WHERE pid IN (" . implode(',', $studentIds) . ") AND exercise_id=?")
            ->execute($instructor, $exerciseId);
    }
}

