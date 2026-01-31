<?php

declare(strict_types=1);

/*
 * DCA: tl_dc_course_event
 * Kursveranstaltung: konkrete Durchführung einer Kurs‑Vorlage (tl_dc_dive_course)
 */

use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\Date;
use Contao\DC_Table;
use Contao\StringUtil;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\CourseEventLabelListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\CourseEventOnSubmitListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\InstructorOptionsListener;

$GLOBALS['TL_DCA']['tl_dc_course_event'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_dc_course_event_schedule'],
        'enableVersioning' => true,
        'markAsCopy' => 'headline',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'alias' => 'index',
                'course_id' => 'index',
                'published,start,stop' => 'index'
            ],
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
            'fields' => ['title', 'dateStart', 'course_id'],
            'format' => '%s <span style="color:#999;">(%s) [Kurs‑Vorlage: %s]</span>',
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
            '!schedule' => [
                'label' => ['Zeitplan', 'Zeitplan der Veranstaltung bearbeiten'],
                'href' => 'table=tl_dc_course_event_schedule',
                'icon' => 'calendar.svg',
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
        'default' => '{title_legend},title,alias,course_id;
                      {time_legend},dateStart,dateEnd,location;
                      {details_legend},instructor,max_participants,price,description;
                      {publish_legend},published,start,stop'
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'sorting' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'title' => [
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) BINARY NOT NULL default ''",
        ],
        'course_id' => [
            'label' => ['Kurs‑Vorlage', 'Referenz auf tl_dc_dive_course'],
            'inputType' => 'select',
            'foreignKey' => 'tl_dc_dive_course.title',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w33 clr'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'dateStart' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 clr wizard'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'dateEnd' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'location' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 128, 'tl_class' => 'w25 clr'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'instructor' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_event']['instructor'],
            'inputType' => 'select',
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w25'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'max_participants' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'tl_class' => 'w25'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'price' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'price', 'tl_class' => 'w25'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'description' => [
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
        'published' => [
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
        ],
    ],
];
