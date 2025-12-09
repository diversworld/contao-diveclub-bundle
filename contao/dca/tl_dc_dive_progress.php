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

$GLOBALS['TL_DCA']['tl_dive_progress'] = [

    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_dive_course',     // Fortschritt gehört immer zu einem Kurs
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'student,module' => 'index'
            ]
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['student', 'module'],
            'flag' => 1,
            'panelLayout' => 'filter;search,limit',
            'headerFields' => ['title', 'instructor', 'course_type']
        ],
        'label' => [
            'fields' => ['student', 'module', 'status'],
            'format' => '<strong>Schüler:</strong> %s | <strong>Modul:</strong> %s | <span style="color:#999">%s</span>'
        ],
        'operations' => [
            'edit' => [
                'label' => ['Bearbeiten'],
                'href' => 'act=edit',
                'icon' => 'edit.svg'
            ],
            'delete' => [
                'label' => ['Löschen'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'Wirklich löschen?\'))return false;"'
            ],
            'show' => [
                'label' => ['Details'],
                'href' => 'act=show',
                'icon' => 'show.svg'
            ]
        ]
    ],

    // Palettes
    'palettes' => [
        'default' => '{student_legend},student,module,status,date,instructor;{notes_legend},notes;',
    ],

    // Fields
    'fields' => [

        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],

        'pid' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],

        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],

        'student' => [
            'label' => ['Schüler', 'Teilnehmer dieses Kurses'],
            'inputType' => 'select',
            'foreignKey' => 'tl_dive_participant.lastname',
            'eval' => ['mandatory' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],

        'module' => [
            'label' => ['Modul', 'Absolviertes Theorie-Modul'],
            'inputType' => 'select',
            'foreignKey' => 'tl_dive_module.title',
            'eval' => ['mandatory' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],

        'status' => [
            'label' => ['Status', 'Fortschritt bei diesem Modul'],
            'inputType' => 'select',
            'options' => ['passed', 'failed', 'inprogress'],
            'reference' => [
                'passed' => 'Bestanden',
                'failed' => 'Nicht bestanden',
                'inprogress' => 'In Bearbeitung'
            ],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default ''"
        ],

        'date' => [
            'label' => ['Datum', 'Tag der Durchführung'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(10) NOT NULL default ''"
        ],

        'instructor' => [
            'label' => ['Instruktor', 'Wer hat dieses Modul abgenommen?'],
            'inputType' => 'text',
            'eval' => ['maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => "varchar(128) NOT NULL default ''"
        ],

        'notes' => [
            'label' => ['Bemerkungen', 'Interne Notizen'],
            'inputType' => 'textarea',
            'eval' => ['tl_class' => 'clr', 'style' => 'height:80px'],
            'sql' => "text NULL"
        ],
    ]
];

