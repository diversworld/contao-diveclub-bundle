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
use Contao\System;
use Diversworld\ContaoDiveclubBundle\DataContainer\DcDiveCourse;
use Diversworld\ContaoDiveclubBundle\Model\DcDiveCourseModel;

/**
 * Table tl_dc_divecourse
 */
$GLOBALS['TL_DCA']['tl_dc_dive_course'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_content'],
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
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['title'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title', 'course_type'],
            'format' => '%s <span style="color:#999;">[%s]</span>',
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
                'label' => ['Bearbeiten', 'Kurs bearbeiten'],
            ],
            'children',
            'copy',
            'delete' => [
                'label' => ['Löschen', 'Kurs löschen'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'Wirklich löschen?\'))return false;"',
            ],
            'show',
            'toggle'
        ]
    ],
    'palettes' => [
        '__selector__' => ['published'],
        'default' => '{title_legend},title,course_type,instructor,max_participants,price,requirements;
                      {publish_legend},published',
    ],
    'subpalettes' => [
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'pid' => [
            'foreignKey' => 'tl_calendar_events.title',
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'title' => [
            'label' => ['Kurstitel', 'Titel des Tauchkurses'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w33'],
            'save_callback' => ['tl_dc_dive_course', 'generateAlias'],
            'sql' => "varchar(255) BINARY NOT NULL default ''",
        ],
        'course_type' => [
            'label' => ['Kurstyp', 'Art des Kurses (z. B. OWD, AOWD, Rescue)'],
            'inputType' => 'select',
            'options' => ['OWD', 'AOWD', 'Rescue', 'Nitrox', 'Specialty'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'instructor' => [
            'label' => ['Tauchlehrer', 'Verantwortlicher Ausbilder'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => "varchar(128) NOT NULL default ''",
        ],
        'max_participants' => [
            'label' => ['Max. Teilnehmer', 'Begrenzung der Teilnehmerzahl'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'digit', 'tl_class' => 'w50'],
            'sql' => "smallint(5) unsigned NOT NULL default 0",
        ],
        'price' => [
            'label' => ['Preis', 'Teilnahmegebühr (optional)'],
            'inputType' => 'text',
            'eval' => ['rgxp' => 'price', 'tl_class' => 'w50 clr'],
            'sql' => "decimal(10,2) NOT NULL default '0.00'",
        ],
        'requirements' => [
            'label' => ['Voraussetzungen', 'Voraussetzungen oder Hinweise'],
            'inputType' => 'textarea',
            'eval' => ['style' => 'height:60px'],
            'sql' => "text NULL",
        ],
        'published' => [
            'label' => ['Veröffentlicht', 'Kurs aktivieren/deaktivieren'],
            'inputType' => 'checkbox',
            'sql' => "char(1) NOT NULL default ''",
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
    ],
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property DcDiveCourse $Courses
 *
 * @internal
 */
class tl_dc_dive_course extends Backend
{
    /**
     * Auto-generate the event alias if it has not been set yet
     *
     * @param mixed $varValue
     * @param DataContainer $dc
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function generateAlias(mixed $varValue, DataContainer $dc): mixed
    {
        $aliasExists = function (string $alias) use ($dc): bool {
            return $this->Database->prepare("SELECT id FROM tl_dc_dive_course WHERE alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0;
        };

        // Generate alias if there is none
        if (!$varValue) {
            $varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->headline, DcDiveCourseModel::findByPk($dc->activeRecord->pid)->jumpTo, $aliasExists);
        } elseif (preg_match('/^[1-9]\d*$/', $varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
        } elseif ($aliasExists($varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }
}
