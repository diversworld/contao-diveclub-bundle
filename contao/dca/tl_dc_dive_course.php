<?php

declare(strict_types=1);

/*
 * This file is part of ContaoDiveclubBundle.
 *
 * (c) Diversworld, Eckhard Becker 2025 <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

use Contao\Backend;
use Contao\BackendUser;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\DataContainer\DcDiveCourse;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\CourseCategoryOptionsCallback;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\CourseTypeOptionsCallback;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\DiveCourseAliasListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\InstructorOptionsListener;
use Diversworld\ContaoDiveclubBundle\Model\DcDiveCourseModel;

/**
 * Table tl_dc_dive_course
 */
$GLOBALS['TL_DCA']['tl_dc_dive_course'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_dc_course_modules', 'tl_content'],
        'enableVersioning' => true,
        'markAsCopy' => 'headline',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'tstamp' => 'index',
                'alias' => 'index',
                'published,start,stop' => 'index'
            ]
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTABLE,
            'fields' => ['title'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'sort,filter;search,limit',
        ],
        'label' => [
            'fields' => ['title', 'course_type'],
            'format' => '%s <span style="color:#999;">[%s]</span>',
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations' => [
            'edit',
            '!modules' => [
                'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['modules'],
                'href' => 'table=tl_dc_course_modules',
                'icon' => 'modules.svg',
                'primary' => true,
                'showInHeader' => true
            ],
            'children',
            'copy',
            'cut',
            'delete',
            'toggle',
            'show',
            'new_after' => [
                'label' => ['Neu danach', 'Neue Zuordnung hinzufügen'],
                'href' => 'act=create&amp;mode=1',
                'icon' => 'new.svg', // Das Plus-Icon
                'attributes' => 'onclick="Backend.getScrollOffset()"'
            ],
        ]
    ],
    'palettes' => [
        '__selector__' => ['addImage', 'overwriteMeta'],
        'default' => '{first_legend},title,alias;
                      {course_legend},course_type;
                      {details_section},category,description;
                      {requirenment_section},requirements;
                      {image_legend},addImage;
                      {publish_legend},published,start,stop;'
    ],
    'subpalettes' => [
        'addImage' => 'singleSRC,fullsize,size,floating,overwriteMeta',
        'overwriteMeta' => 'alt,imageTitle,imageUrl,caption'
    ],
    'fields' => [
        'id' => [
            'sql' => "int unsigned NOT NULL auto_increment"
        ],
        'pid' => [
            'foreignKey' => 'tl_calendar_events.title',
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'sorting' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'tstamp' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['title'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['alias'],
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w33'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'course_type' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['course_type'],
            'inputType' => 'select',
            'options_callback' => [CourseTypeOptionsCallback::class, '__invoke'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w33'],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'dateStart' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['dateStart'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql' => "int unsigned NULL",
        ],
        'dateEnd' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['dateEnd'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql' => "int unsigned NULL",
        ],
        'instructor' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['instructor'],
            'inputType' => 'select',
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w25'],
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'max_participants' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['max_participants'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'digit', 'tl_class' => 'w33'],
            'sql' => "smallint(5) unsigned NOT NULL default 0",
        ],
        'price' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['price'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'price', 'tl_class' => 'w33'],
            'sql' => "decimal(10,2) NOT NULL default '0.00'",
        ],
        'description' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['description'],
            'inputType' => 'textarea',
            'exclude' => true,
            'search' => true,
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => 'text NULL'
        ],
        'requirements' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['requirements'],
            'inputType' => 'textarea',
            'exclude' => true,
            'search' => true,
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => 'text NULL'
        ],
        'category' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['category'],
            'inputType' => 'select',
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'options_callback' => [CourseCategoryOptionsCallback::class, '__invoke'],
            'eval' => ['includeBlankOption' => true, 'tl_class' => 'w33'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'addImage' => [
            'inputType' => 'checkbox',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['addImage'],
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w33 clr'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'overwriteMeta' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['overwriteMeta'],
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50 clr'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'singleSRC' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['singleSRC'],
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'extensions' => '%contao.image.valid_extensions%', 'mandatory' => true],
            'sql' => "binary(16) NULL"
        ],
        'alt' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['alt'],
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'imageTitle' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['imageTitle'],
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'size' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_dive_course']['size'],
            'inputType' => 'imageSize',
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => ['rgxp' => 'natural', 'includeBlankOption' => true, 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'w50 clr'],
            'options_callback' => static function () {
                return System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance());
            },
            'sql' => "varchar(64) NOT NULL default ''"
        ],
        'imageUrl' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_courses']['imageUrl'],
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 2048, 'dcaPicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(2048) NOT NULL default ''"
        ],
        'fullsize' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_courses']['fullsize'],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'caption' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_courses']['caption'],
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'allowHtml' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'floating' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_courses']['floating'],
            'inputType' => 'radioTable',
            'options' => ['above', 'left', 'right', 'below'],
            'eval' => ['cols' => 4, 'tl_class' => 'w50'],
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'sql' => "varchar(32) NOT NULL default 'above'"
        ],
        'remarks' => [
            'inputType' => 'textarea',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_courses']['remarks'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => 'text NULL'
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

