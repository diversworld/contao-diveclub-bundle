<?php

declare(strict_types=1);

/*
 * DCA: tl_dc_course_exercises
 * Übungen / Skills pro Modul
 */

use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\CourseExerciseAliasListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\CourseExerciseOptionsListener;

$GLOBALS['TL_DCA']['tl_dc_course_exercises'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_course_modules',
        'enableVersioning' => true,
        'markAsCopy' => 'headline',
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
            'headerFields' => ['shortcode', 'title'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'sort,filter;search,limit',
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
            'children',
            'copy',
            'cut',
            'delete',
            'toggle',
            'show',
        ],
    ],
    'palettes' => [
        'default' => '{title_legend},title,alias;
                      {detail_legend},description,required,duration;
                      {notes_legend},notes;
                      {publish_legend},published,start,stop'
    ],
    'fields' => [
        'id' => [
            'sql' => "int unsigned NOT NULL auto_increment"
        ],
        'pid' => [
            'foreignKey' => 'tl_dc_course_modules.title',
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
        'alias' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_exercises']['alias'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'description' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_exercises']['description'],
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
        'prerequisites' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_exercises']['prerequisites'],
            'inputType' => 'select',
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'clr w50'],
            'sql' => "int unsigned NOT NULL default 0",
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
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_exercises']['published'],
            'inputType' => 'checkbox',
            'toggle' => true,
            'eval' => ['tl_class' => 'w50 clr'],
            'sql' => ['type' => 'boolean', 'default' => false],
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

