<?php

declare(strict_types=1);

/*
 * DCA: tl_dc_students
 * Tauchschüler (kann alternativ tl_member referenzieren)
 */

use Contao\Backend;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\StudentSyncCallback;

$GLOBALS['TL_DCA']['tl_dc_students'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_dc_course_students'],
        'enableVersioning' => true,
        'onsubmit_callback' => [
            [StudentSyncCallback::class, '__invoke']
        ],
        'markAsCopy' => 'headline',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'lastname' => 'index',
                'phone' => 'index',
                'email' => 'index',
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTABLE,
            'fields' => ['sorting', 'lastname', 'firstname'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'sort,filter;search,limit',
        ],
        'label' => [
            'fields' => ['lastname', 'firstname', 'dateOfBirth', 'phone', 'email'],
            'format' => '%s, %s <span style="color:#b3b3b3; padding-left:8px;">%s</span>, %s, %s',
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
            '!courses' => [
                'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['courses'],
                'href' => 'table=tl_dc_course_students',
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
        ],
    ],
    'palettes' => [
        '__selector__' => ['allowLogin'],
        'default' => '{personal_legend},firstname,lastname,dateOfBirth,gender,language;
                      {contact_legend},street,postal,city,state,country,email,phone,mobile;
                      {medical_legend},medical_ok,notes;
                      {login_legend},allowLogin;
                      {publish_legend},published,start,stop'
    ],
    'subpalettes' => [
        'allowLogin' => 'username,memberGroups'
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
        'firstname' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['firstname'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 128, 'tl_class' => 'w25'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'lastname' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['lastname'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 128, 'tl_class' => 'w25'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'dateOfBirth' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['dateOfBirth'],
            'inputType' => 'text',
            'eval' => array('rgxp' => 'date', 'datepicker' => true, 'feEditable' => true, 'feGroup' => 'personal', 'tl_class' => 'w25 wizard clr'),
            'sql' => "varchar(11) NOT NULL default ''"
        ],
        'gender' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['gender'],
            'inputType' => 'select',
            'options' => array('male', 'female', 'other'),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array('includeBlankOption' => true, 'feEditable' => true, 'feGroup' => 'personal', 'tl_class' => 'w25'),
            'sql' => "varchar(32) NOT NULL default ''"
        ],
        'street' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['street'],
            'search' => true,
            'inputType' => 'text',
            'eval' => array('maxlength' => 255, 'feEditable' => true, 'feGroup' => 'address', 'tl_class' => 'w25 clr'),
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'postal' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['postal'],
            'search' => true,
            'inputType' => 'text',
            'eval' => array('maxlength' => 32, 'feEditable' => true, 'feGroup' => 'address', 'tl_class' => 'w25 clr'),
            'sql' => "varchar(32) NOT NULL default ''"
        ],
        'city' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['city'],
            'search' => true,
            'sorting' => true,
            'inputType' => 'text',
            'eval' => array('maxlength' => 255, 'feEditable' => true, 'feGroup' => 'address', 'tl_class' => 'w25'),
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'state' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['state'],
            'sorting' => true,
            'inputType' => 'text',
            'eval' => array('maxlength' => 64, 'feEditable' => true, 'feGroup' => 'address', 'tl_class' => 'w25 clr'),
            'sql' => "varchar(64) NOT NULL default ''"
        ],
        'country' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['coubtry'],
            'filter' => true,
            'sorting' => true,
            'inputType' => 'select',
            'eval' => array('includeBlankOption' => true, 'chosen' => true, 'feEditable' => true, 'feGroup' => 'address', 'tl_class' => 'w25'),
            'options_callback' => static fn() => System::getContainer()->get('contao.intl.countries')->getCountries(),
            'sql' => "varchar(6) NOT NULL default ''"
        ],
        'language' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['language'],
            'filter' => true,
            'inputType' => 'select',
            'eval' => array('includeBlankOption' => true, 'chosen' => true, 'feEditable' => true, 'feGroup' => 'personal', 'tl_class' => 'w25'),
            'options_callback' => static fn() => System::getContainer()->get('contao.intl.locales')->getLocales(),
            'sql' => "varchar(64) NOT NULL default ''"
        ],
        'email' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['email'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'email', 'maxlength' => 255, 'tl_class' => 'w25 clr'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'phone' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['phone'],
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w25'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'mobile' => array
        (
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['mobile'],
            'search' => true,
            'inputType' => 'text',
            'eval' => array('maxlength' => 64, 'rgxp' => 'phone', 'decodeEntities' => true, 'feEditable' => true, 'feGroup' => 'contact', 'tl_class' => 'w25'),
            'sql' => "varchar(64) NOT NULL default ''"
        ),
        'medical_ok' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['medical_ok'],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'allowLogin' => [
            'label' => ['Anmeldung ermöglichen', 'Erstellt einen Datensatz in der Mitgliederverwaltung.'],
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w33'],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'username' => [
            'label' => &$GLOBALS['TL_LANG']['tl_member']['username'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'rgxp' => 'alnum', 'unique' => true, 'spaceToUnderscore' => true, 'maxlength' => 64, 'tl_class' => 'w25'],
            'sql' => "varchar(64) NOT NULL default ''"
        ],
        'memberGroups' => [
            'label' => &$GLOBALS['TL_LANG']['tl_member']['groups'],
            'inputType' => 'checkbox',
            'foreignKey' => 'tl_member_group.name',
            'eval' => ['multiple' => true, 'mandatory' => true, 'tl_class' => 'clr'],
            'sql' => "blob NULL"
        ],
        'memberId' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'notes' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['notes'],
            'inputType' => 'textarea',
            'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['published'],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 clr'],
            'sql' => ['type' => 'boolean', 'default' => true],
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

class tl_dc_students extends Backend
{

}
