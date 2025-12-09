<?php

declare(strict_types=1);

/*
 * DCA: tl_dc_students
 * Tauchschüler (kann alternativ tl_member referenzieren)
 */

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_dc_students'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'lastname' => 'index',
                'email' => 'index',
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTABLE,
            'fields' => ['lastname', 'firstname'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['lastname', 'firstname', 'birthdate'],
            'format' => '%s, %s <span style="color:#b3b3b3; padding-left:8px;">%s</span>',
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
            'courses' => [
                'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['courses'],
                'href' => 'table=tl_dc_course_students',
                'icon' => 'calendar.svg',
            ],
            'copy',
            'delete',
            'show',
            'toggle',
        ],
    ],
    'palettes' => [
        'default' => '{personal_legend},firstname,lastname,birthdate,email,phone;{medical_legend},medical_ok,notes;{publish_legend},published'
    ],

    'fields' => [
        'id' => ['sql' => "int(10) unsigned NOT NULL auto_increment"],
        'tstamp' => ['sql' => "int(10) unsigned NOT NULL default 0"],
        'firstname' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['firstname'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'lastname' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['lastname'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'birthdate' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['birthdate'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'email' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['email'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'email', 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'phone' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['phone'],
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'medical_ok' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['medical_ok'],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'notes' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['notes'],
            'inputType' => 'textarea',
            'eval' => ['tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['published'],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 clr'],
            'sql' => ['type' => 'boolean', 'default' => true],
        ],
    ],
];
