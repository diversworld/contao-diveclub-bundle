<?php

declare(strict_types=1);

/*
 * DCA: tl_dc_student_exercises
 * Status/Auswertung einer Übung pro Schüler
 */

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_dc_student_exercises'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'exercise_id' => 'index',
                'student_id' => 'index',
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTABLE,
            'fields' => ['exercise_id', 'student_id', 'dateCompleted'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['exercise_id', 'student_id', 'status'],
            'format' => '%s — %s <span style="color:#b3b3b3; padding-left:8px;">%s</span>',
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
            'delete',
            'show',
            'toggle',
        ],
    ],

    'palettes' => [
        'default' => '{exercise_legend},exercise_id,student_id;{result_legend},status,dateCompleted,instructor;{notes_legend},notes'
    ],

    'fields' => [
        'id' => ['sql' => "int(10) unsigned NOT NULL auto_increment"],
        'tstamp' => ['sql' => "int(10) unsigned NOT NULL default 0"],
        'exercise_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['exercise_id'],
            'inputType' => 'select',
            'foreignKey' => 'tl_dc_course_exercises.title',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'student_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['student_id'],
            'inputType' => 'select',
            'foreignKey' => 'tl_dc_students.lastname',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'status' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['status'],
            'inputType' => 'select',
            'options' => ['pending', 'ok', 'repeat', 'failed'],
            'reference' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['status'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default 'pending'",
        ],
        'dateCompleted' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['dateCompleted'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'instructor' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['instructor'],
            'inputType' => 'text',
            'eval' => ['maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'notes' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_student_exercises']['notes'],
            'inputType' => 'textarea',
            'eval' => ['tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
    ],
];
