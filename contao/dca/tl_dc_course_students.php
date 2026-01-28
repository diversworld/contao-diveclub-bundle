<?php

declare(strict_types=1);

/*
 * DCA: tl_dc_course_students
 * Junction table: welche Schüler nehmen an welchem Kurs teil
 */

use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\CourseStudentLabelCallback;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\CourseStudentOnSubmitListener;

$GLOBALS['TL_DCA']['tl_dc_course_students'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_students',
        'ctable' => ['tl_dc_student_exercises'],
        'enableVersioning' => true,
        'markAsCopy' => 'headline',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'course_id' => 'index',
                'event_id' => 'index'
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['sorting', 'course_id'],
            'headerFields' => ['firstname', 'lastname', 'birthdate', 'phone', 'email'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'sort,filter;search,limit',
        ],
        'label' => [
            'fields' => ['course_id', 'status', 'registered_on', 'payed'],
            //'format' => '%s — Status: <span style="color:#b3b3b3; padding-left:8px;">%s</span> (Angemeldet am: %s), Bezahlt: %s',
            //'label_callback' => null,
            'showColumns' => true,
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
            '!progress' => [
                'label' => ['Fortschritt', 'Übungen dieses Kurses dokumentieren'],
                'href' => 'table=tl_dc_student_exercises',
                'icon' => 'forward.svg', // Oder ein passendes Icon
                'primary' => true,
                'showInHeader' => true
            ],
            'copy',
            'cut',
            'delete',
            'toggle',
            'show',
        ],
    ],
    'palettes' => [
        'default' => '{course_legend},course_id,event_id;
                      {status_legend},status,registered_on,payed,brevet,dateBrevet,
                      {notes_legend},notes;
                      {publish_legend},published,start,stop',
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'pid' => [
            'foreignKey' => 'tl_dc_students.lastname',
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'sorting' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'course_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_students']['course_id'],
            'inputType' => 'select',
            'foreignKey' => 'tl_dc_dive_course.title',
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'tl_class' => 'w33'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'event_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_students']['event_id'],
            'inputType' => 'select',
            'foreignKey' => 'tl_dc_course_event.title',
            'eval' => ['includeBlankOption' => true, 'tl_class' => 'w33'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'status' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_students']['status'],
            'inputType' => 'select',
            'reference' => &$GLOBALS['TL_LANG']['tl_dc_course_students']['itemStatus'],
            'options' => &$GLOBALS['TL_LANG']['tl_dc_course_students']['itemStatus'],
            'eval' => ['tl_class' => 'w25'],
            'sql' => "varchar(16) NOT NULL default 'registered'",
        ],
        'registered_on' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_students']['registered_on'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w25 wizard'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'payed' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_students']['payed'],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w25'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'brevet' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_students']['brevet'],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w25 clr'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'dateBrevet' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_students']['dateBrevet'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w25 wizard'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'notes' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_students']['notes'],
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

