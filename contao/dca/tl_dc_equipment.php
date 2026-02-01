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

use Contao\DataContainer;
use Contao\DC_Table;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\EquipmentAliasListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\EquipmentLabelCallback;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\EquipmentManufacturerOptionsCallback;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\EquipmentSizeOptionsCallback;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\EquipmentSubTypeOptionsCallback;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\EquipmentTypeOptionsCallback;

/**
 * Table tl_dc_tanks
 */
$GLOBALS['TL_DCA']['tl_dc_equipment'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
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
            'fields' => ['title', 'alias', 'published'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;sort,search,limit'
        ],
        'label' => [
            'fields' => ['type', 'subType', 'title', 'manufacturer', 'model', 'size', 'rentalFee', 'status'],
            'label_callback' => [EquipmentLabelCallback::class, '__invoke'],
            'showColumns' => true,
            'format' => '%s',
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
            'toggle',
            'show',
        ],
    ],
    'palettes' => [
        '__selector__' => ['addNotes'],
        'default'   => '{title_legend},title,type,subType,alias;
                        {status_legend},status,rentalFee;
                        {details_legend},manufacturer,model,color,size,serialNumber,buyDate;
                        {notes_legend},addNotes;
                        {publish_legend},published,start,stop;'
    ],
    'subpalettes' => [
        'addNotes' => 'notes',
    ],
    'fields' => [
        'id' => [
            'sql' => "int unsigned NOT NULL auto_increment"
        ],
        'pid' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'tstamp' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'title' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['title'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'alias' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w33 clr'],
            'save_callback' => [
                [EquipmentAliasListener::class, '__invoke']
            ],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'type' => [
            'inputType' => 'select',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['type'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'options_callback' => [EquipmentTypeOptionsCallback::class, '__invoke'],
            'flag' => DataContainer::SORT_INITIAL_LETTERS_ASC,
            'eval' => array('includeBlankOption' => true, 'submitOnChange' => true, 'mandatory' => true, 'tl_class' => 'w25 clr'),
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'subType' => [
            'inputType' => 'select',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['subType'],
            'exclude' => true,
            'options_callback' => [EquipmentSubTypeOptionsCallback::class, '__invoke'],
            'eval' => ['includeBlankOption' => true, 'mandatory' => true, 'tl_class' => 'w25',],
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'rentalFee' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['rentalFee'],
            'exclude' => true,
            'search' => false,
            'filter' => true,
            'sorting' => false,
            'eval' => ['rgxp' => 'digit', 'mandatory' => false, 'tl_class' => 'w25'],
            'sql' => "DECIMAL(10,2) NOT NULL default '0.00'"
        ],
        'manufacturer' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['manufacturer'],
            'inputType' => 'select',
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'options_callback' => [EquipmentManufacturerOptionsCallback::class, '__invoke'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w25 clr'],
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'model' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['model'],
            'inputType' => 'text',
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => array('mandatory' => false, 'includeBlankOption' => true, 'tl_class' => 'w25'),
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'color' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['color'],
            'inputType' => 'text',
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => array('mandatory' => false, 'tl_class' => 'w25'),
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'size' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['size'],
            'inputType' => 'select',
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'options_callback' => [EquipmentSizeOptionsCallback::class, '__invoke'],
            'eval' => ['mandatory' => false, 'includeBlankOption' => true, 'tl_class' => 'w25'],
            'sql' => "int unsigned NOT NULL default 0",
        ],
        'serialNumber' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['serialNumber'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval' => ['mandatory' => false, 'maxlength' => 50, 'tl_class' => 'w25 clr'],
            'sql' => "varchar(50) NOT NULL default ''"
        ],
        'buyDate' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['buyDate'],
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'filter' => true,
            'flag' => DataContainer::SORT_YEAR_DESC,
            'eval' => ['rgxp' => 'date', 'doNotCopy' => false, 'datepicker' => true, 'tl_class' => 'w25 wizard'],
            'sql' => "bigint(20) NULL"
        ],
        'status' => [
            'inputType' => 'select',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['status'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'options' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['itemStatus'],
            'reference' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['itemStatus'],
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'addNotes' => [
            'inputType' => 'checkbox',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['addNotes'],
            'exclude' => true,
            'eval' => ['submitOnChange' => false, 'tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'notes' => [
            'inputType' => 'textarea',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['notes'],
            'exclude' => true,
            'search' => false,
            'filter' => false,
            'sorting' => false,
            'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr'],
            'sql' => 'text NULL'
        ],
        'published' => [
            'inputType' => 'checkbox',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['published'],
            'toggle' => true,
            'filter' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_DESC,
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'start' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['start'],
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 clr wizard'],
            'sql' => "varchar(10) NOT NULL default ''"
        ],
        'stop' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_equipment']['stop'],
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''"
        ]
    ]
];
