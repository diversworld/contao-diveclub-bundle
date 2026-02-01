<?php

declare(strict_types=1);

/*
 * DCA: tl_dc_course_modules
 * Module eines Kurses (child table von tl_dc_courses)
 */

use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\CourseModuleAliasListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\CourseModuleOptionsListener;

$GLOBALS['TL_DCA']['tl_dc_course_modules'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_dive_course',
        'ctable' => ['tl_dc_course_exercises'],
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
            'headerFields' => ['title', 'dateStart', 'dateEnd'],
            'flag' => DataContainer::SORT_BOTH,
            'panelLayout' => 'sort;filter,search,limit',
        ],
        'label' => [
            'fields' => ['shortcode', 'title', 'mandatory'],
            'format' => '%s — <span style="color:#86AF35FF; padding-left:8px;">%s — %s</span>',
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
            '!exercises' => [
                'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['exercises'],
                'href' => 'table=tl_dc_course_exercises',
                'icon' => 'jobs.svg',
                'primary' => true,
                'showInHeader' => true
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
        '__selector__' => [],
        'default' => '{title_legend},title,alias;
                      {details_legend},shortcode,mandatory,preModule,description;
                      {prerequisite_legend},prerequisites;
                      {publish_legend},published,start,stop'
    ],
    'fields' => [
        'id' => [
            'sql' => "int unsigned NOT NULL auto_increment",
        ],
        'pid' => [
            'foreignKey' => 'tl_dc_dive_course.title',
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'sorting' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'tstamp' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['title'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['alias'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'shortcode' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['shortcode'],
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'filter' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 32, 'tl_class' => 'w25 clr'],
            'sql' => "varchar(32) NOT NULL default ''"
        ],
        'mandatory' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['mandatory'],
            'inputType' => 'checkbox',
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['tl_class' => 'w25'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'description' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['description'],
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
        'prerequisites' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['prerequisites'],
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
        'preModule' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['preModule'],
            'inputType' => 'select',
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w25'],
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['published'],
            'inputType' => 'checkbox',
            'search' => true,
            'filter' => true,
            'sorting' => true,
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

