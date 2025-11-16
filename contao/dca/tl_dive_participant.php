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

$GLOBALS['TL_DCA']['tl_dive_participant'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_dive_course',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
            ],
        ],
    ],
    'fields' => [
        'id' => ['sql' => "int(10) unsigned NOT NULL auto_increment"],
        'pid' => ['sql' => "int(10) unsigned NOT NULL default 0"],
        'tstamp' => ['sql' => "int(10) unsigned NOT NULL default 0"],
        'member_id' => ['sql' => "int(10) unsigned NOT NULL default 0"],
        'firstname' => [
            'label' => ['Vorname', ''],
            'inputType' => 'text',
            'eval' => ['mandatory' => true],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'lastname' => [
            'label' => ['Nachname', ''],
            'inputType' => 'text',
            'eval' => ['mandatory' => true],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'email' => [
            'label' => ['E-Mail', ''],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'email', 'mandatory' => true],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'status' => [
            'label' => ['Status', ''],
            'inputType' => 'select',
            'options' => ['pending', 'confirmed', 'cancelled'],
            'eval' => ['includeBlankOption' => false],
            'sql' => "varchar(16) NOT NULL default 'pending'",
        ],
        'medical_ok' => [
            'label' => ['Ärztliche Tauglichkeit vorhanden', ''],
            'inputType' => 'checkbox',
            'sql' => "char(1) NOT NULL default ''",
        ],
        'created_at' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
    ],
];
