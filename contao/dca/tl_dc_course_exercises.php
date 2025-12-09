<?php

declare(strict_types=1);

/*
 * DCA: tl_dc_course_exercises
 * Übungen / Skills pro Modul
 */

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_dc_course_exercises'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_course_modules',
        'ctable' => ['tl_dc_student_exercises'],
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'tstamp' => 'index',
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['sorting', 'title'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title', 'required'],
            'format' => '%s <span style="color:#b3b3b3; padding-left:8px;">%s</span>',
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
            'student_exercises' => [
                'label' => &$GLOBALS['TL_LANG']['tl_dc_course_exercises']['student_exercises'],
                'href' => 'table=tl_dc_student_exercises',
                'icon' => 'accounts.svg',
            ],
            'copy',
            'delete',
            'show',
            'toggle',
        ],
    ],

    'palettes' => [
        'default' => '{title_legend},title,alias;{detail_legend},description,required,duration,notes;{publish_legend},published'
    ],

    'fields' => [
        'id' => ['sql' => "int(10) unsigned NOT NULL auto_increment"],
        'pid' => ['foreignKey' => 'tl_dc_course_modules.title', 'sql' => "int(10) unsigned NOT NULL default 0"],
        'tstamp' => ['sql' => "int(10) unsigned NOT NULL default 0"],
        'sorting' => ['label' => &$GLOBALS['TL_LANG']['tl_dc_course_exercises']['sorting'], 'sql' => "int(10) unsigned NOT NULL default 0"],
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_exercises']['title'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_exercises']['alias'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) BINARY NOT NULL default ''",
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
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'duration' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_exercises']['duration'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'notes' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_exercises']['notes'],
            'inputType' => 'textarea',
            'eval' => ['tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_exercises']['published'],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 clr'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
    ],
];
