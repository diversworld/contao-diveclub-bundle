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
use Diversworld\ContaoDiveclubBundle\DataContainer\DcEquipmentType;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\DcEquipmentTypeLabelCallback;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\DcEquipmentTypeSubTypeOptionsCallback;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\DcEquipmentTypeTitleOptionsCallback;
use Psr\Log\LoggerInterface;

/**
 * Table tl_dc_tanks
 */
$GLOBALS['TL_DCA']['tl_dc_equipment_type'] = [
    'config'            => [
        'dataContainer'     => DC_Table::class,
        'ctable'            => ['tl_dc_equipment'],
        'enableVersioning'  => true,
        'sql'               => array(
            'keys' => array(
                'id'        => 'primary',
                'tstamp'    => 'index',
                'alias'     => 'index',
                'published,start,stop' => 'index'
            )
        ),
    ],
    'list'              => [
        'sorting'           => [
            'mode'          => DataContainer::MODE_SORTED,
            'fields'        => ['title','subType'],
            'flag'          => DataContainer::MODE_SORTED,
            'panelLayout'   => 'filter;search,limit'
        ],
        'label'         => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
            'fields'        => ['title','subType'],
            'label_callback' => [DcEquipmentTypeLabelCallback::class, 'getLabelCallback'],
        ],
        'global_operations' => [
            'all'       => [
                'href'      => 'act=select',
                'class'     => 'header_edit_all',
                'attributes'=> 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations' => [
            'edit',
            'children',
            'copy',
            'delete',
            'toggle',
            'show',
        ],
    ],
    'palettes'      => [
        '__selector__'  => ['addNotes'],
        'default'       => '{title_legend},title,subType,alias;
                            {notes_legend},addNotes;
                            {publish_legend},published,start,stop;'
    ],
    'subpalettes'   => [
        'addNotes'  => 'notes',
    ],
    'fields'        => [
        'id'            => [
            'sql'               => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp'        => [
            'sql'               => "int(10) unsigned NOT NULL default 0"
        ],
        'title'         => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_type']['title'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => [DcEquipmentTypeTitleOptionsCallback::class, 'getEquipmentTypes'],
            'eval'              => ['submitOnChange' => true, 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w33'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'alias'         => [
            'search'            => true,
            'inputType'         => 'text',
            'eval'              => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w33'],
            'save_callback'     => [
                ['tl_dc_equipment_type', 'generateAlias']
            ],
            'sql'               => "varchar(255) BINARY NOT NULL default ''"
        ],
        'subType'          => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_type']['subType'],
            'inputType'         => 'select',
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => [DcEquipmentTypeSubTypeOptionsCallback::class, 'getSubTypes'],
            'eval'              => ['mandatory' => false, 'submitOnChange' => true, 'tl_class' => 'w33'],
            'sql'               => "varchar(255) NOT NULL default ''",
        ],
        'addNotes'      => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_type']['addNotes'],
            'exclude'           => true,
            'inputType'         => 'checkbox',
            'eval'              => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'notes'         => [
            'inputType'         => 'textarea',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_type']['notes'],
            'exclude'           => true,
            'search'            => false,
            'filter'            => false,
            'sorting'           => false,
            'eval'              => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql'               => 'text NULL'
        ],
        'published'     => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_type']['published'],
            'toggle'            => true,
            'filter'            => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_DESC,
            'eval'              => ['doNotCopy' => true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'start'         => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_type']['start'],
            'eval'              => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 clr wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'stop'          => [
            'inputType'         => 'text',
            'eval'              => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ]
    ]
];

class tl_dc_equipment_type extends Backend
{
    private LoggerInterface $logger;
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
        $aliasExists = static function (string $alias) use ($dc): bool {
            $result = Database::getInstance()
                ->prepare("SELECT id FROM tl_dc_equipment_type WHERE alias=? AND id!=?")
                ->execute($alias, $dc->id);
            return $result->numRows > 0;
        };

        // Generate the alias if there is none
        if (!$varValue) {
            $varValue = System::getContainer()->get('contao.slug')->generate(
                $dc->activeRecord->title,
                [],
                $aliasExists
            );
        } elseif (preg_match('/^[1-9]\d*$/', $varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
        } elseif ($aliasExists($varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }

}
