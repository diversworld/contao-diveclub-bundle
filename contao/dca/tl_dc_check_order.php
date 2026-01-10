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

/**
 * Table tl_dc_check_order
 */
$GLOBALS['TL_DCA']['tl_dc_check_order'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_check_proposal',
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'tstamp' => 'index',
                'memberId' => 'index',
            ]
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['memberId'],
            'headerFields' => ['title', 'vendorName', 'proposalDate'],
            'flag' => DataContainer::SORT_ASC,
            'panelLayout' => 'filter;sort,search,limit'
        ],
        'label' => [
            'fields' => ['memberId', 'tankId', 'totalPrice', 'status'],
            'label_callback' => ['tl_dc_check_order', 'listOrders'],
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg'
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg'
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? 'Löschen?') . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg'
            ]
        ]
    ],
    'palettes' => [
        'default' => '{member_legend},memberId,firstname,lastname,email,phone;{tank_legend},tankId,tankData;{order_legend},selectedArticles,totalPrice,status;{notes_legend},notes;'
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
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'memberId' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'foreignKey' => 'tl_member.CONCAT(firstname, " ", lastname)',
            'eval' => ['chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
            'relation' => ['type' => 'hasOne', 'load' => 'lazy']
        ],
        'firstname' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
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
        'tankId' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'foreignKey' => 'tl_dc_tanks.title',
            'eval' => ['chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
            'relation' => ['type' => 'hasOne', 'load' => 'lazy']
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
            'reference'         => &$GLOBALS['TL_LANG']['tl_dc_check_order']['sizes'],
            'options'           => &$GLOBALS['TL_LANG']['tl_dc_check_order']['sizes'],
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
            'options_callback' => ['tl_dc_check_order', 'getArticleOptions'],
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
            'options' => ['ordered', 'delivered', 'checked', 'canceled'],
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
    public function listOrders($row): string
    {
        $member = Database::getInstance()->prepare("SELECT firstname, lastname FROM tl_member WHERE id=?")->execute($row['memberId']);
        $tank = Database::getInstance()->prepare("SELECT title, serialNumber FROM tl_dc_tanks WHERE id=?")->execute($row['tankId']);

        $memberName = $member->numRows ? $member->firstname . ' ' . $member->lastname : 'Unbekannt';
        $tankName = $tank->numRows ? $tank->title . ' (' . $tank->serialNumber . ')' : 'Manuelle Eingabe';

        return sprintf(
            '<div class="tl_content_left">%s <span style="color:#999;padding-left:3px">(%s)</span></div><div class="tl_content_right">%s € - %s</div>',
            $memberName,
            $tankName,
            number_format((float)$row['totalPrice'], 2, ',', '.'),
            $row['status']
        );
    }

    public function getArticleOptions(DataContainer $dc): array
    {
        $options = [];
        $articles = Database::getInstance()->prepare("SELECT id, title, articlePriceBrutto FROM tl_dc_check_articles WHERE pid=?")->execute($dc->activeRecord->pid);
        while ($articles->next()) {
            $options[$articles->id] = $articles->title . ' (' . $articles->articlePriceBrutto . ' €)';
        }
        return $options;
    }
}
