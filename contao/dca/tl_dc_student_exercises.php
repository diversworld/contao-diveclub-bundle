<?php

declare(strict_types=1);

/*
 * DCA: tl_dc_student_exercises
 * Status/Auswertung einer Übung pro Schüler
 */

use Contao\Backend;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\CourseListener;

$GLOBALS['TL_DCA']['tl_dc_student_exercises'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_course_students',
        'enableVersioning' => true,
        'markAsCopy' => 'headline',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'exercise_id' => 'index'
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['sorting', 'exercise_id', 'dateCompleted'],
            'headerFields' => ['course_id', 'status', 'registered_on'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'sort,filter;search,limit',
        ],
        'label' => [
            'fields' => ['exercise_id', 'status'],
            'format' => '%s — <span style="color:#b3b3b3; padding-left:8px;">%s</span>',
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
            'complete' => [
                'label' => ['Übung abschließen', 'Status auf OK setzen und Datum eintragen'],
                'href' => 'key=completeExercise',
                'icon' => 'ok.svg',
                'primary' => true,
            ],
            'children',
            'copy',
            'cut',
            'delete',
            'toggle',
            'show',
        ],
    ],

    'palettes' => [
        'default' => '{exercise_legend},module_id,exercise_id;
                      {result_legend},status,dateCompleted,instructor;
                      {notes_legend},notes;
                      {publish_legend},published,start,stop',
    ],

    'fields' => [
        'id' => [
            'sql' => "int unsigned NOT NULL auto_increment"
        ],
        'pid' => [
            'options_callback' => [CourseListener::class, 'onCourseStudentOptions'],
            'sql' => "int unsigned NOT NULL default 0",
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy', 'table' => 'tl_dc_course_students']
        ],
        'sorting' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'tstamp' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'exercise_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['exercise_id'],
            'inputType' => 'select',
            'options_callback' => [CourseListener::class, 'onExerciseOptions'],
            'eval' => ['mandatory' => false, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "int unsigned NOT NULL default 0",
            'relation' => ['type' => 'hasOne', 'load' => 'lazy', 'table' => 'tl_dc_course_exercises']
        ],
        'module_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['title'],
            'inputType' => 'select',
            'options_callback' => [CourseListener::class, 'onExerciseModuleOptions'],
            'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50', 'readonly' => true],
            'sql' => "int unsigned NOT NULL default 0",
            'relation' => ['type' => 'hasOne', 'load' => 'lazy', 'table' => 'tl_dc_course_modules']
        ],
        'status' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['status'],
            'inputType' => 'select',
            'reference' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['itemStatus'],
            'options' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['itemStatus'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default 'pending'",
        ],
        'dateCompleted' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['dateCompleted'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "int NULL",
        ],
        'instructor' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['instructor'],
            'inputType' => 'select',
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'notes' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['notes'],
            'inputType' => 'textarea',
            'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_courses']['published'],
            'toggle' => true,
            'filter' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_DESC,
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
        ]
    ],
];

