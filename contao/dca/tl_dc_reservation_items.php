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
use Diversworld\ContaoDiveclubBundle\DataContainer\DcReservation;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\ItemReservationCallbackListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\ReservationItemsHeaderCallback;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\ReservationItemsLabelCallback;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\ReservationItemsSubTypeOptionsCallback;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Diversworld\ContaoDiveclubBundle\Model\DcEquipmentModel;
use Diversworld\ContaoDiveclubBundle\Model\DcRegulatorsModel;
use Diversworld\ContaoDiveclubBundle\Model\DcTanksModel;

/**
 * Table tl_dc_reservation
 */
$GLOBALS['TL_DCA']['tl_dc_reservation_items'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_reservation',
        'enableVersioning' => true,
        'onsubmit_callback' => [ItemReservationCallbackListener::class, '__invoke'],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'tstamp' => 'index',
                'published,start,stop' => 'index'
            ]
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['item_type', 'reservation_status', 'created_at', 'updated_at'],
            'headerFields' => ['title', 'member_id', 'reservedFor', 'reservation_status', 'created_at', 'updated_at'],
            'header_callback' => [ReservationItemsHeaderCallback::class, '__invoke'],
            'flag' => DataContainer::SORT_ASC,
            'panelLayout' => 'filter;sort,search,limit'
        ],
        'label' => [
            'fields' => ['item_type', 'item_id', 'types', 'sub_type', 'reservation_status', 'created_at', 'updated_at'],
            'showColumns' => true,
            'format' => '%s, %s - %s | %s - %s - %s - %s!',
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
            'copy',
            'delete',
            'show'
        ]
    ],
    'palettes' => [
        '__selector__' => ['item_type', 'addNotes'], // "item_type" als selektierbares Feld definieren
        'default' => '{title_legend},item_type,item_id;
                                    {details_legend},reservation_status;
                                    {reservation_legend},reserved_at,picked_up_at,returned_at,created_at,updated_at;
                                    {notes_legend},addNotes;
                                    {publish_legend},published,start,stop;',
    ],
    'subpalettes' => [
        'addNotes' => 'notes',
        'item_type_tl_dc_equipment' => 'types,sub_type', // Subpalette für "tl_dc_equipment_types"
    ],
    'fields' => [
        'id' => [
            'sql' => "int unsigned NOT NULL auto_increment"
        ],
        'tstamp' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'sorting' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'pid' => [
            'foreignKey' => 'tl_dc_reservation.title',
            'sql' => "int unsigned NOT NULL default 0",
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'item_type' => [
            'inputType' => 'select',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['item_type'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'options' => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes'],
            'reference' => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes'],
            'eval' => ['includeBlankOption' => true, 'submitOnChange' => true, 'chosen' => true, 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'types' => [
            'inputType' => 'select',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['types'], // Sprachvariable
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['mandatory' => false, 'submitOnChange' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w25'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'sub_type' => [
            'inputType' => 'select',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['sub_type'], // Sprachvariable
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['mandatory' => false, 'submitOnChange' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w25'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'reservation_status' => [
            'inputType' => 'select',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['reservation_status'],
            'default' => 'reserved',
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'options' => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'],
            'reference' => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'],
            'eval' => ['includeBlankOption' => true, 'submitOnChange' => true, 'chosen' => true, 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'item_id' => [
            'inputType' => 'select',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['asset_id'],
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'explanation' => 'selected_asset',
            'eval' => [
                'includeBlankOption' => true,
                'submitOnChange' => true,
                'chosen' => true,
                'mandatory' => true,
                'maxlength' => 255,
                'helpwizard' => true,
                'tl_class' => 'w25'
            ],
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'reserved_at' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['reserved_at'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 clr wizard'],
            'sql' => "int NULL"
        ],
        'picked_up_at' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['picked_up_at'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'submitOnChange' => true, 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql' => "int NULL"
        ],
        'returned_at' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['returned_at'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'submitOnChange' => true, 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql' => "int NULL"
        ],
        'created_at' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['created_at'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql' => "int NULL"
        ],
        'updated_at' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['updated_at'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql' => "int NULL"
        ],
        'addNotes' => [
            'inputType' => 'checkbox',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['addNotes'],
            'exclude' => true,
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'notes' => [
            'inputType' => 'textarea',
            'exclude' => true,
            'search' => false,
            'filter' => true,
            'sorting' => false,
            'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr'],
            'sql' => 'text NULL'
        ],
        'published' => [
            'toggle' => true,
            'filter' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_DESC,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'start' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 clr wizard'],
            'sql' => "varchar(10) NOT NULL default ''"
        ],
        'stop' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''"
        ]
    ]
];

