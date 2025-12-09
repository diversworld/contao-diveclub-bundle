<?php

declare(strict_types=1);

/*
 * DCA: tl_dc_course_modules
 * Module eines Kurses (child table von tl_dc_courses)
 */

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_dc_course_modules'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_courses',
        'ctable' => ['tl_dc_course_exercises'],
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
            'fields' => ['title'],
            'flag' => DataContainer::SORT_ASC,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label' => [
            'fields' => ['title', 'dateStart', 'dateEnd'],
            'format' => '%s <span style="color:#b3b3b3; padding-left:8px;">%s — %s</span>',
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
            'exercises' => [
                'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['exercises'],
                'href' => 'table=tl_dc_course_exercises',
                'icon' => 'modules.svg',
            ],
            'copy',
            'delete',
            'show',
            'toggle',
        ],
    ],

    'palettes' => [
        '__selector__' => [],
        'default' => '{title_legend},title,alias;{time_legend},dateStart,dateEnd;{details_legend},description;{publish_legend},published'
    ],

    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'pid' => [
            'foreignKey' => 'tl_dc_courses.title',
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['title'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'search' => true,
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['alias'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) BINARY NOT NULL default ''",
        ],
        'description' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['description'],
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
        'dateStart' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['dateStart'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'dateEnd' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['dateEnd'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['published'],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 clr'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
    ],
];
