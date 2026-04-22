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
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\TankOptionsListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\OrderArticleOptionsListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\OrderLabelListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\OrderSizeOptionsListener;

/**
 * Table tl_dc_check_order
 */
$GLOBALS['TL_DCA']['tl_dc_check_order'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_check_booking',
        'enableVersioning' => true,
        'oncreate_callback' => [],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'tstamp' => 'index',
            ]
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['size'],
            'headerFields' => ['bookingNumber', 'lastname', 'firstname', 'bookingDate'],
            'flag' => DataContainer::SORT_ASC,
            'disableGrouping' => false,
            'panelLayout' => 'filter;sort,search,limit'
        ],
        'label' => [
            'fields' => ['serialNumber', 'size', 'totalPrice', 'status'],
            'format' => '%s (%sL) - %s € [%s]',
            'label_callback' => [OrderLabelListener::class, '__invoke'],
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
        'default' => '{booking_legend},bookingId;
                      {tank_legend},tankId,serialNumber,manufacturer,bazNumber,size,o2clean;
                      {order_legend},selectedArticles,totalPrice,status;{notes_legend},notes;'
    ],
    'fields' => [
        'id' => [
            'sql' => "int unsigned NOT NULL auto_increment"
        ],
        'pid' => [
            'foreignKey' => 'tl_dc_check_booking.bookingNumber',
            'sql' => "int unsigned NOT NULL default 0",
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy']
        ],
        'sorting' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'tstamp' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'bookingId' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'maxlength' => 64, 'tl_class' => 'w33'],
            'sql' => "varchar(64) NOT NULL default ''"
        ],
        'tankId' => [
            'exclude' => true,
            'inputType' => 'select',
            'options_callback' => [TankOptionsListener::class, '__invoke'],
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w25 clr', 'submitOnChange' => true],
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'serialNumber'      => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_check_order']['serialNumber'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval' => ['mandatory' => false, 'maxlength' => 50, 'tl_class' => 'w25 clr'],
            'sql'               => "varchar(50) NOT NULL default ''"
        ],
        'manufacturer'      => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_check_order']['manufacturer'],
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
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_check_order']['bazNumber'],
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
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_check_order']['size'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'reference' => &$GLOBALS['TL_LANG']['tl_dc_check_order']['sizes'],
            'options_callback' => [OrderSizeOptionsListener::class, '__invoke'],
            'eval' => ['includeBlankOption' => true, 'submitOnChange' => true, 'tl_class' => 'w25'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'o2clean'           => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_tanks']['o2clean'],
            'exclude'           => true,
            'filter'            => true,
            'eval'              => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql' => "char(1) NOT NULL default ''"
        ],
        'tankData' => [
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['style' => 'height:60px', 'tl_class' => 'clr'],
            'sql' => "blob NULL"
        ],
        'selectedArticles' => [
            'exclude' => true,
            'inputType' => 'checkboxWizard',
            'options_callback' => [OrderArticleOptionsListener::class, '__invoke'],
            'eval' => ['multiple' => true, 'tl_class' => 'clr', 'submitOnChange' => true],
            'sql' => "blob NULL"
        ],
        'totalPrice' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'rgxp' => 'digit', 'tl_class' => 'w50'],
            'sql' => "decimal(10,2) NOT NULL default '0.00'"
        ],
        'status' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options' => ['ordered', 'delivered', 'checked', 'canceled', 'pickedup'],
            'reference' => &$GLOBALS['TL_LANG']['tl_dc_check_order']['status_reference'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default 'ordered'"
        ],
        'notes' => [
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => "text NULL"
        ]
    ]
];
