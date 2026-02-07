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
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use Contao\Input;
use Contao\CoreBundle\Monolog\ContaoContext;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\TankLabelListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\MemberOptionsListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\TankAliasListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\TankCheckDateListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\TankCalendarOptionsListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\TankPriceListener;

/**
 * Table tl_dc_tanks
 */
$GLOBALS['TL_DCA']['tl_dc_tanks'] = [
    'config'            => [
        'dataContainer'     => DC_Table::class,
        'enableVersioning'  => true,
        'ondelete_callback' => [],
        'sql'               => [
            'keys'          => [
                'id'            => 'primary',
                'title'         => 'index',
                'alias'         => 'index',
                'serialNumber'  => 'index',
                'published,start,stop' => 'index'
            ]
        ],
    ],
    'list'              => [
        'sorting'           => [
            'mode'              => DataContainer::MODE_SORTABLE,
            'fields'            => ['title','owner','manufacturer','size','lastCheckDate','nextCheckDate','o2clean','status'],
            'flag'              => DataContainer::SORT_ASC,
            'panelLayout'       => 'filter;sort,search,limit',
        ],
        'label'             => [
            'fields'            => ['title','owner','serialNumber','manufacturer','size','o2clean','lastCheckDate','nextCheckDate','status'],
            'showColumns'       => true,
            'format'            => '%s',
            'label_callback'    => [TankLabelListener::class, '__invoke'],
        ],
        'global_operations' => [
            'all'               => [
                'href'          => 'act=select',
                'class'         => 'header_edit_all',
                'attributes'    => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations'        => [
            'edit',
            'copy',
            'delete',
            'toggle',
            'show',
        ],
    ],
    'palettes'          => [
        '__selector__'      => ['addNotes'],
        'default'           => '{title_legend},title,alias,status,rentalFee;
                                {details_legend},serialNumber,manufacturer,bazNumber,size,o2clean,owner,checkId,lastCheckDate,nextCheckDate,lastOrder;
                                {notes_legend},addNotes;
                                {publish_legend},published,start,stop;'
    ],
    'subpalettes'       => [
        'addNotes'     => 'notes',
    ],
    'fields'            => [
        'id'                => [
            'sql' => "int unsigned NOT NULL auto_increment"
        ],
        'tstamp'            => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'title'             => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['title'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => true, 'maxlength'=>255, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'alias'             => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['alias'],
            'search'            => true,
            'eval'              => ['rgxp'=>'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
            'save_callback' => [
                [TankAliasListener::class, '__invoke']
            ],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'serialNumber'      => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['serialNumber'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25'],
            'sql'               => "varchar(50) NOT NULL default ''"
        ],
        'manufacturer'      => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['manufacturer'],
            'exclude'           => true,
            'search'            => false,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => false, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'bazNumber'         => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['bazNumber'],
            'exclude'           => true,
            'search'            => false,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => false, 'tl_class' => 'w25'],
            'sql'               => "varchar(50) NOT NULL default ''"
        ],
        'size'              => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['size'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'reference'         => &$GLOBALS['TL_LANG']['tl_dc_tanks']['sizes'],
            'options'           => &$GLOBALS['TL_LANG']['tl_dc_tanks']['sizes'],
            'eval'              => ['includeBlankOption' => true, 'tl_class' => 'w25'],
            'sql'               => "varchar(20) NOT NULL default ''",
        ],
        'o2clean'           => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['o2clean'],
            'exclude'           => true,
            'filter'            => true,
            'eval'              => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'checkId'           => [
            'inputType'         => 'select',                        // Typ ist "select"
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['checkId'],
            'foreignKey'        => 'tl_calendar_events.title',      // Zeigt den Titel des Events als Auswahl
            'relation'          => ['type' => 'hasOne', 'load' => 'lazy'], // Relationstyp
            'options_callback'  => [TankCalendarOptionsListener::class, '__invoke'],  // Option Callback
            'save_callback'     => [
                [TankCheckDateListener::class, '__invoke']
            ],
            'eval'              => [
                'includeBlankOption'=> true,                      // Option "Bitte wählen" hinzufügen
                'chosen'            => true,                       // Dropdown mit Suchfunktion
                'submitOnChange'    => true,                       // Lade-Seite bei Änderung reload
                'tl_class'          => 'w33 clr'                   // Layout-Klasse
            ],
            'sql' => "int unsigned NOT NULL default 0" // Datenbankspalte
        ],
        'lastCheckDate'     => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['lastCheckDate'],
            'exclude'           => true,
            'sorting'           => true,
            'filter'            => true,
            'flag'              => DataContainer::SORT_YEAR_DESC,
            'eval'              => ['submitOnChange' => true, 'rgxp'=>'date', 'mandatory'=>false, 'doNotCopy'=>true, 'datepicker'=>true, 'tl_class'=>'w33 wizard'],
            'sql' => "int unsigned NULL"
        ],
        'nextCheckDate'     => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['nextCheckDate'],
            'exclude'           => true,
            'sorting'           => true,
            'filter'            => true,
            'flag'              => DataContainer::SORT_YEAR_DESC,
            'eval'              => ['submitOnChange' => true,'rgxp'=>'date', 'doNotCopy'=>false, 'datepicker'=>true, 'tl_class'=>'w33 wizard'],
            'sql' => "int unsigned NULL"
        ],
        'lastOrder'         => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['lastOrder'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'eval'              => ['maxlength' => 255, 'tl_class' => 'w33 clr'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'rentalFee'             => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_types']['rentalFee'],
            'exclude'           => true,
            'search'            => false,
            'filter'            => true,
            'sorting'           => true,
            'save_callback'     => [[TankPriceListener::class, '__invoke']],
            'eval'              => [ 'mandatory'=>false, 'tl_class' => 'w25'], // Beachten Sie "rgxp" für Währungsangaben
            'sql'               => "DECIMAL(10,2) NOT NULL default '0.00'"
        ],
        'owner'             => [
            'inputType'         => 'select',                                        // Typ ist "select"
            'options_callback'  => [MemberOptionsListener::class, '__invoke'],              // Optionen über Callback holen
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['owner'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'foreignKey'        => 'tl_member.id',
            'relation'          => ['type' => 'belongsTo', 'load' => 'lazy'],       // Relationstyp
            'eval'              => [
                'includeBlankOption'=> true,                                        // Option "Bitte wählen" hinzufügen
                'chosen'            => true,                                        // Dropdown mit Suchfunktion
                'mandatory'         => false,                                       // Nicht obligatorisch
                'tl_class'          => 'w33 clr'                                    // Layout-Klasse
            ],
            'sql' => "int unsigned NOT NULL default 0"            // Datenbankspalte
        ],
        'status'        => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['status'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options'           => &$GLOBALS['TL_LANG']['tl_dc_tanks']['itemStatus'],
            'reference'         => &$GLOBALS['TL_LANG']['tl_dc_tanks']['itemStatus'],
            'eval'              => ['includeBlankOption' => true, 'submitOnChange' => true, 'chosen'   => true, 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'addNotes'          => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['addNotes'],
            'exclude'           => true,
            'eval'              => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'notes'             => [
            'inputType'         => 'textarea',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['notes'],
            'exclude'           => true,
            'search'            => false,
            'filter'            => false,
            'sorting'           => false,
            'eval'              => ['style'=>'height:60px', 'decodeEntities'=>true, 'rte'=>'tinyMCE', 'basicEntities'=>true, 'tl_class'=>'clr'],
            'sql'               => 'text NULL'
        ],
        'published'         => [
            'inputType'         => 'checkbox',
            'flag'              => DataContainer::SORT_INITIAL_LETTER_DESC,
            'toggle'            => true,
            'filter'            => true,
            'eval'              => ['doNotCopy'=>true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'start'             => [
            'inputType'         => 'text',
            'eval'              => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 clr wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'stop'              => [
            'inputType'         => 'text',
            'eval'              => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ]
    ]
];
