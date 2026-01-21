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
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\OrderArticleOptionsListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\OrderLabelListener;

/**
 * Table tl_dc_check_order
 */
$GLOBALS['TL_DCA']['tl_dc_check_order'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_check_booking',
        'enableVersioning' => true,
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
            'fields' => ['id'],
            'headerFields' => ['bookingNumber', 'lastname', 'firstname', 'bookingDate'],
            'flag' => DataContainer::SORT_ASC,
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
                      {tank_legend},tankData,serialNumber,manufacturer,bazNumber,size,o2clean;
                      {order_legend},selectedArticles,totalPrice,status;{notes_legend},notes;'
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'pid' => [
            'foreignKey' => 'tl_dc_check_booking.bookingNumber',
            'sql' => "int(10) unsigned NOT NULL default 0",
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy']
        ],
        'sorting' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'bookingId' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''"
        ],
        'serialNumber'      => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_check_order']['serialNumber'],
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
        'tankData' => [
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['style' => 'height:60px', 'tl_class' => 'clr'],
            'sql' => "blob NULL"
        ],
        'selectedArticles' => [
            'exclude' => true,
            'inputType' => 'checkbox',
            'options_callback' => [OrderArticleOptionsListener::class, '__invoke'],
            'eval' => ['multiple' => true],
            'sql' => "blob NULL"
        ],
        'totalPrice' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50'],
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

class tl_dc_check_order extends Backend
{
    public function generatePdfButton($row, $href, $label, $title, $icon, $attributes)
    {
        // For orders, we use the parent booking ID to generate the full PDF
        $url = System::getContainer()->get('router')->generate('dc_check_order_pdf', ['id' => $row['pid']]);

        return '<a href="' . $url . '" title="' . StringUtil::specialchars($title) . '" ' . $attributes . ' target="_blank">' . Image::getHtml($icon, $label) . '</a> ';
    }
}
