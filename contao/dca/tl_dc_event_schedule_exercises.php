<?php

declare(strict_types=1);

/*
 * DCA: tl_dc_event_schedule_exercises
 * Übungen pro Zeitplan-Eintrag (geerbte Übungen aus Kursstammdaten)
 */

use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\InstructorOptionsListener;

$GLOBALS['TL_DCA']['tl_dc_event_schedule_exercises'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_course_event_schedule',
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'exercise_id' => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['sorting'],
            'headerFields' => ['module_id', 'planned_at'],
            'panelLayout' => 'sort,filter;search,limit',
        ],
        'label' => [
            'fields' => ['title', 'exercise_id'],
            'format' => '%s gude <span style="color:#b3b3b3; padding-left:8px;">[ID: %s]</span>',
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
            'show',
        ],
    ],
    'palettes' => [
        'default' => '{exercise_legend},title,exercise_id,planned_at,instructor;
                      {detail_legend},description,required,duration;
                      {notes_legend},notes;
                      {publish_legend},published'
    ],
    'fields' => [
        'id' => [
            'sql' => "int unsigned NOT NULL auto_increment"
        ],
        'pid' => [
            'foreignKey' => 'tl_dc_course_event_schedule.id',
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'sorting' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'tstamp' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_exercises']['title'],
            'inputType' => 'text',
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'exercise_id' => [
            'label' => ['Original-Übung', 'Referenz auf die Stammdaten-Übung'],
            'inputType' => 'select',
            'foreignKey' => 'tl_dc_course_exercises.title',
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'description' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_exercises']['description'],
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
        'required' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_exercises']['required'],
            'inputType' => 'checkbox',
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'duration' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_exercises']['duration'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'tl_class' => 'w50'],
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'notes' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_exercises']['notes'],
            'inputType' => 'textarea',
            'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
        'planned_at' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'instructor' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_event_schedule_exercises']['instructor'],
            'inputType' => 'select',
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w25'],
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'published' => [
            'toggle' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => true]
        ],
    ],
];
