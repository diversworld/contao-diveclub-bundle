<?php

declare(strict_types=1);

/*
 * DCA: tl_dc_course_students
 * Junction table: welche Schüler nehmen an welchem Kurs teil
 */

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_dc_course_students'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'course_id' => 'index',
                'student_id' => 'index',
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTABLE,
            'fields' => ['course_id', 'student_id'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['course_id', 'student_id', 'status'],
            'format' => '%s — %s <span style="color:#b3b3b3; padding-left:8px;">%s</span>',
            'label_callback' => null,
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
        'default' => '{course_legend},course_id,student_id;{status_legend},status,registered_on,notes'
    ],

    'fields' => [
        'id' => ['sql' => "int(10) unsigned NOT NULL auto_increment"],
        'tstamp' => ['sql' => "int(10) unsigned NOT NULL default 0"],
        'course_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_students']['course_id'],
            'inputType' => 'select',
            'foreignKey' => 'tl_dc_courses.title',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'student_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_students']['student_id'],
            'inputType' => 'select',
            'foreignKey' => 'tl_dc_students.lastname',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'status' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_students']['status'],
            'inputType' => 'select',
            'options' => ['registered', 'active', 'completed', 'dropped'],
            'reference' => &$GLOBALS['TL_LANG']['tl_dc_course_students']['status'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default 'registered'",
        ],
        'registered_on' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_students']['registered_on'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'notes' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_students']['notes'],
            'inputType' => 'textarea',
            'eval' => ['tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
    ],
];
