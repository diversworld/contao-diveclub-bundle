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
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\BookingLabelListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\MemberOptionsListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\BookingPdfButtonListener;

/**
 * Table tl_dc_check_booking
 */
$GLOBALS['TL_DCA']['tl_dc_check_booking'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_check_proposal',
        'ctable' => ['tl_dc_check_order'],
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'tstamp' => 'index',
                'bookingNumber' => 'unique',
            ]
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['bookingDate DESC'],
            'headerFields' => ['title', 'vendorName', 'proposalDate'],
            'flag' => DataContainer::SORT_DESC,
            'panelLayout' => 'filter;sort,search,limit'
        ],
        'label' => [
            'fields' => ['bookingNumber', 'lastname', 'firstname', 'totalPrice', 'status'],
            'format' => '[%s] %s, %s - %s €',
            'label_callback' => [BookingLabelListener::class, '__invoke'],
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
            'children',
            'pdf' => [
                'label' => &$GLOBALS['TL_LANG']['tl_dc_check_booking']['pdf'],
                'href' => 'key=pdf',
                'icon' => 'bundles/diversworldcontaodiveclub/icons/pdf.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
                'button_callback' => [BookingPdfButtonListener::class, '__invoke']
            ],
            'copy',
            'cut',
            'delete',
            'toggle',
            'show'
        ]
    ],
    'palettes' => [
        'default' => '{booking_legend},bookingNumber,bookingDate,totalPrice,status,paid;{member_legend},memberId,firstname,lastname,email,phone;{notes_legend},notes;'
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'pid' => [
            'foreignKey' => 'tl_dc_check_proposal.title',
            'sql' => "int(10) unsigned NOT NULL default 0",
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy']
        ],
        'sorting' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'bookingNumber' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default ''"
        ],
        'bookingDate' => [
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_DESC,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "int(10) unsigned NOT NULL default 0"
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
            'reference' => &$GLOBALS['TL_LANG']['tl_dc_check_booking']['status_reference'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default 'ordered'"
        ],
        'paid' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''"
        ],
        'memberId' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options_callback' => [MemberOptionsListener::class, '__invoke'],
            'eval' => ['chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50 clr'],
            'sql' => "int(10) unsigned NOT NULL default 0",
            'foreignKey' => 'tl_member.lastname',
            'relation' => ['type' => 'hasOne', 'load' => 'lazy']
        ],
        'firstname' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50 clr'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'lastname' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'email' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'rgxp' => 'email', 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'phone' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'rgxp' => 'phone', 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''"
        ],
        'notes' => [
            'inputType' => 'textarea',
            'exclude' => true,
            'eval' => ['style' => 'height:60px', 'tl_class' => 'clr'],
            'sql' => 'text NULL'
        ],
    ]
];
