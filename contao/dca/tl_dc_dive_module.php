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

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_dc_dive_module'] = [

    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ]
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['title'],
            'flag' => 1,
            'panelLayout' => 'search,limit'
        ],
        'label' => [
            'fields' => ['title', 'shortcode'],
            'format' => '%s <span style="color:#999">[%s]</span>'
        ],
        'operations' => [
            'edit' => [
                'label' => ['Bearbeiten', 'Modul bearbeiten'],
                'href' => 'act=edit',
                'icon' => 'edit.svg'
            ],
            'delete' => [
                'label' => ['Löschen', 'Modul löschen'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'Wirklich löschen?\'))return false;"'
            ],
            'show' => [
                'label' => ['Details', 'Modul-Details'],
                'href' => 'act=show',
                'icon' => 'show.svg'
            ]
        ]
    ],

    // Palettes
    'palettes' => [
        '__selector__' => [],
        'default' => '{title_legend},title,shortcode,description;',
    ],

    // Fields
    'fields' => [

        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],

        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],

        'title' => [
            'label' => ['Modultitel', 'Name des Theorie-Moduls'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],

        'shortcode' => [
            'label' => ['Kurzcode', 'z. B. M1, T2, etc.'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 32, 'tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default ''"
        ],

        'description' => [
            'label' => ['Beschreibung', 'Kurzbeschreibung des Theorie-Moduls'],
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => "text NULL"
        ],
    ]
];
