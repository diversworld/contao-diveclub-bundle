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
use Contao\CoreBundle\EventListener\Widget\HttpUrlListener;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\ProposalAliasListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\ProposalEventVendorInfoListener;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\ProposalTuvListButtonListener;

/**
 * Table tl_dc_check_proposal
 */
$GLOBALS['TL_DCA']['tl_dc_check_proposal'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_dc_check_articles', 'tl_dc_check_booking'],
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
            'flag' => DataContainer::SORT_ASC,
            'panelLayout' => 'filter;sort,search,limit'
        ],
        'label' => [
            'fields' => ['title', 'vendorName', 'checkId'],
            'format' => '%s %s %s',
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
            '!orders' => [
                'href' => 'table=tl_dc_check_booking',
                'icon' => 'forward.svg', //'bundles/diversworldcontaodiveclub/icons/order.svg', // Icon muss ggf. noch erstellt werden
                'label' => &$GLOBALS['TL_LANG']['tl_dc_check_proposal']['orders'],
                'primary' => true,
                'showInHeader' => true
            ],
            'copy',
            'cut',
            'delete',
            'toggle',
            'show',
            '!tuv_list' => [
                'label' => &$GLOBALS['TL_LANG']['tl_dc_check_proposal']['tuv_list'],
                'href' => 'key=tuv_list',
                'icon' => 'bundles/diversworldcontaodiveclub/icons/pdf.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
                'button_callback' => [ProposalTuvListButtonListener::class, '__invoke'],
                'primary' => true,
                'showInHeader' => true
            ],
            'new_after' => [
                'label' => ['Neu danach', 'Neue Zuordnung hinzufügen'],
                'href' => 'act=create&amp;mode=1',
                'icon' => 'new.svg', // Das Plus-Icon
                'attributes' => 'onclick="Backend.getScrollOffset()"'
            ],
        ]
    ],
    'palettes' => [
        '__selector__' => ['addNotes'],
        'default' => '{title_legend},title,alias;
                                {details_legend},proposalDate,checkId;
                                {vendor_legend},vendorName,vendorWebsite,vendorStreet,vendorPostal,vendorCity,vendorEmail,vendorPhone,vendorMobile;
                                {notes_legend},notes;
                                {publish_legend},published,start,stop;'
    ],
    'subpalettes' => [
    ],
    'fields' => [
        'id' => [
            'sql' => "int unsigned NOT NULL auto_increment"
        ],
        'sorting' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'tstamp' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'title' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_check_proposal']['title'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval' => ['mandatory' => true, 'maxlength' => 25, 'tl_class' => 'w33'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'alias' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w33'],
            'save_callback' => [[ProposalAliasListener::class, '__invoke']],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'checkId' => [
            'inputType' => 'select', // 'select' für Dropdown
            'foreignKey' => 'tl_calendar_events.title',
            //'options_callback'  => [['tl_dc_check_proposal', 'getCalenarOptions']],
            'options_callback' => function () {
                $options = [];
                $db = Database::getInstance();
                $result = $db->execute("SELECT id, title FROM tl_calendar_events WHERE addCheckInfo = '1'");

                if ($result->numRows > 0) {
                    $data = $result->fetchAllAssoc();
                    $options = array_column($data, 'title', 'id');
                }
                return $options;
            },
            'save_callback' => [
                [ProposalEventVendorInfoListener::class, '__invoke']
            ], // Spezifische Callback-Methode
            'eval' => [
                'includeBlankOption' => true, // Ermöglicht eine leere Auswahl als Standardvalue
                'mandatory' => false,
                'chosen' => true, // Bessere Darstellung des Dropdowns
                'tl_class' => 'w25', // CSS-Klasse fürs Layout
            ],
            'sql' => "int unsigned NULL default 0",
        ],
        'proposalDate' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_check_proposal']['proposalDate'],
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_DAY_DESC,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w25 clr wizard'],
            'sql' => "int NULL"
        ],
        'vendorName' => [
            'exclude' => true,
            'flag' => SORT_STRING,
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'eval' => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w33',],
            'sql' => "varchar(255) NULL default ''",
        ],
        'vendorWebsite' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => HttpUrlListener::RGXP_NAME, 'maxlength' => 255, 'feEditable' => true, 'feGroup' => 'contact', 'tl_class' => 'w33'],
            'sql' => "varchar(255) NULL default ''"
        ],
        'vendorStreet' => [
            'exclude' => true,
            'flag' => SORT_STRING,
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'eval' => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w33 clr',],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'vendorPostal' => [
            'exclude' => true,
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'eval' => ['maxlength' => 12, 'tl_class' => 'w25',],
            'sql' => "varchar(32) NULL default ''",
        ],
        'vendorCity' => [
            'exclude' => true,
            'flag' => SORT_STRING,
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'eval' => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w33',],
            'sql' => "varchar(255) NULL default ''",
        ],
        'vendorEmail' => [
            'default' => null,
            'exclude' => true,
            'inputType' => 'text',
            'sorting' => true,
            'eval' => ['mandatory' => false, 'maxlength' => 255, 'rgxp' => 'email', 'unique' => false, 'decodeEntities' => true, 'feEditable' => true, 'feGroup' => 'contact', 'tl_class' => 'w25 clr'],
            'sql' => "varchar(255) NULL default ''"
        ],
        'vendorPhone' => [
            'default' => null,
            'exclude' => true,
            'inputType' => 'text',
            'sorting' => true,
            'eval' => ['maxlength' => 64, 'rgxp' => 'phone', 'decodeEntities' => true, 'feEditable' => true, 'feGroup' => 'contact', 'tl_class' => 'w25'],
            'sql' => "varchar(64) NULL default ''"
        ],
        'vendorMobile' => [
            'default' => null,
            'exclude' => true,
            'inputType' => 'text',
            'sorting' => true,
            'eval' => ['maxlength' => 64, 'rgxp' => 'phone', 'decodeEntities' => true, 'feEditable' => true, 'feGroup' => 'contact', 'tl_class' => 'w25'],
            'sql' => "varchar(64) NULL default ''"
        ],
        'addNotes' => [
            'inputType' => 'checkbox',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_reservation']['addNotes'],
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

