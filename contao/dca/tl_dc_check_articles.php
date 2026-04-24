<?php

declare(strict_types=1);

/**
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
 * Table tl_dc_check_articles
 */
$GLOBALS['TL_DCA']['tl_dc_check_articles'] = [
    'config'        => [
        'dataContainer'     => DC_Table::class,
        'ptable'            => 'tl_dc_check_proposal',
        'enableVersioning'  => true,
        'sql'               => [
            'keys'          => [
                'id'            => 'primary',
                'pid'           => 'index',
                'tstamp'        => 'index',
                'alias'         => 'index',
                'published,start,stop' => 'index'
            ]
        ],
    ],
    'list'          => [
        'sorting'           => [
            'mode'          => DataContainer::MODE_PARENT,
            'fields'        => ['title','alias','published'],
            'headerFields'  => ['title', 'vendorName', 'proposalDate'],
            'flag'          => DataContainer::SORT_ASC,
            'panelLayout'   => 'filter;sort,search,limit',
        ],
        'label'             => [
            'fields'        => ['title','articlePriceNetto','articlePriceBrutto'],
            'format'        => '%s - Netto: %s€ Brutto: %s€',
        ],
        'global_operations' => [
            'all'       => [
                'href'      => 'act=select',
                'class'     => 'header_edit_all',
                'attributes'=> 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations'        => [
            'edit',
            'children',
            'copy',
            'cut',
            'delete',
            'toggle',
            'show'
        ]
    ],
    'palettes'      => [
        '__selector__'      => ['addArticleNotes'],
        'default'           => '{title_legend},title,alias;
                                {article_legend},articleSize,articlePriceNetto,articlePriceBrutto,default;
                                {notes_legend},addNotes;
                                {publish_legend},published,start,stop;'
    ],
    'subpalettes'   => [
        'addNotes'          => 'notes',
    ],
    'fields'        => [
        'id'                => [
            'sql' => "int unsigned NOT NULL auto_increment"
        ],
        'pid'               => [
            'foreignKey'        => 'tl_dc_check_proposal.title',
            'sql' => "int unsigned NOT NULL default 0",
            'relation'          => ['type' => 'belongsTo', 'load' => 'lazy'], // Typ anpassen, falls notwendig
        ],
        'sorting' => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'tstamp'            => [
            'sql' => "int unsigned NOT NULL default 0"
        ],
        'title'             => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['articleName'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w33'],
            'sql'               => "varchar(255) NOT NULL default ''",
        ],
        'alias'             => [
            'search'        => true,
            'inputType'     => 'text',
            'eval'          => ['rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w33'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'articleSize'       => [
            'label'         => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['articleSize'],
            'inputType' => 'text',
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            //'options'       => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['sizes'],
            //'reference'     => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['sizes'],
            'eval'          => ['includeBlankOption' => true, 'groupStyle' => 'width:60px', 'tl_class'=>'w25'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'articlePriceNetto' => [
            'label'         => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['articlePriceNetto'],
            'inputType'     => 'text',
            'eval'          => ['submitOnChange' => true, 'tl_class'=>'w25'],
            'sql'           => "DECIMAL(10,2) NOT NULL default '0.00'",
        ],
        'articlePriceBrutto'=> [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['articlePriceBrutto'],
            'inputType'         => 'text',
            'eval'          => ['submitOnChange' => true, 'tl_class'=>'w25'],
            'sql'           => "DECIMAL(10,2) NOT NULL default '0.00'",
        ],
        'default'           => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['default'],
            'inputType'         => 'checkbox',
            'eval'              => ['tl_class'=>'w25'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'addArticleNotes'          => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation']['addNotes'],
            'exclude'           => true,
            'eval'              => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'articleNotes'      => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['articleNotes'],
            'inputType'         => 'textarea',
            'exclude'           => true,
            'search'            => false,
            'filter'            => false,
            'sorting'           => false,
            'eval'              => ['rte' => 'tinyMCE', 'tl_class'=>'w33'],
            'sql'               => 'text NULL',
        ],
        'published'         => [
            'toggle'            => true,
            'filter'            => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_DESC,
            'inputType'         => 'checkbox',
            'eval'              => ['doNotCopy'=>true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false],
        ],
        'start'             => [
            'inputType'         => 'text',
            'eval'              => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 clr wizard'],
            'sql'               => "varchar(10) NOT NULL default ''",
        ],
        'stop'              => [
            'inputType'         => 'text',
            'eval'              => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''",
        ]
    ]
];
