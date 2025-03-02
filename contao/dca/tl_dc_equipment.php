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
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\EquipmentHeaderCallback;
use Diversworld\ContaoDiveclubBundle\Service\TemplateService;
use Psr\Log\LoggerInterface;
use \Diversworld\ContaoDiveclubBundle\DataContainer\DcEquipment;
use Contao\TemplateLoader;

/**
 * Table tl_dc_tanks
 */
$GLOBALS['TL_DCA']['tl_dc_equipment'] = [
    'config'        => [
        'dataContainer'     => DC_Table::class,
        'ptable'            => 'tl_dc_equipment_type',
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
            'mode'              => DataContainer::MODE_PARENT,
            'fields'            => ['title','alias','published'],
            'headerFields'      => ['title', 'subType'],
            'header_callback'   =>[EquipmentHeaderCallback::class, '__invoke'],
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout'       => 'filter;sort,search,limit'
        ],
        'label'             => [
            'fields'            => ['title','manufacturer','model','size'],
            'headerFields'      => ['title', 'subType','alias'],
            'header_callback'   => [EquipmentHeaderCallback::class, '__invoke'],
            'showColumns'       => false,
            'format'            => '%s %s %s %s',
        ],
        'global_operations' => [
            'all'               => [
                'href'          => 'act=select',
                'class'         => 'header_edit_all',
                'attributes'    => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations'        => [
            'edit',
            'copy',
            'delete',
            'toggle',
            'show',
        ],
    ],
    'palettes'          => [
        '__selector__'      => ['addNotes'],
        'default'           => '{title_legend},title,alias;
                                {details_legend},manufacturer,model,color,size,serialNumber,buyDate;
                                {notes_legend},addNotes;
                                {publish_legend},published,start,stop;'
    ],
    'subpalettes'       => [
        'addNotes'     => 'notes',
    ],
    'fields'            => [
        'id'                => [
            'sql'               => "int(10) unsigned NOT NULL auto_increment"
        ],
        'pid'               => [
            'foreignKey'        => 'tl_dc_equipment_type.title',
            'sql'               => "int(10) unsigned NOT NULL default 0",
            'relation'          => ['type' => 'belongsTo', 'load' => 'lazy'], // Typ anpassen, falls notwendig
        ],
        'tstamp'            => [
            'sql'               => "int(10) unsigned NOT NULL default 0"
        ],
        'title'             => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment']['title'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => true, 'maxlength'=>255, 'tl_class' => 'w50'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'alias'             => [
            'search'            => true,
            'inputType'         => 'text',
            'eval'              => ['rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w50'],
            'save_callback' => [
                ['tl_dc_equipment', 'generateAlias']
            ],
            'sql'           => "varchar(255) BINARY NOT NULL default ''"
        ],
        'manufacturer'      => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment']['manufacturer'],
            'inputType'         => 'select',
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => [DcEquipment::class, 'getManufacturers'],
            'eval'              => ['mandatory' => true, 'tl_class' => 'w25 clr'],
            'sql'               => "varchar(255) NOT NULL default ''",
        ],
        'model'             => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment']['model'],
            'inputType'         => 'text',
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'eval'              => array('mandatory' => false, 'tl_class' => 'w25'),
            'sql'               => "varchar(255) NOT NULL default ''",
        ],
        'color'             => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment']['color'],
            'inputType'         => 'text',
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'eval'              => array('mandatory' => false, 'tl_class' => 'w25'),
            'sql'               => "varchar(255) NOT NULL default ''",
        ],
        'size'              => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment']['size'],
            'inputType'         => 'select',
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => [DcEquipment::class, 'getSizes'],
            'eval'              => ['mandatory' => false, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''",
        ],
        'serialNumber'      => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment']['serialNumber'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25 clr'],
            'sql'               => "varchar(50) NOT NULL default ''"
        ],
        'buyDate'           => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment']['buyDate'],
            'exclude'           => true,
            'search'            => true,
            'sorting'           => true,
            'filter'            => true,
            'flag'              => DataContainer::SORT_YEAR_DESC,
            'eval'              => ['submitOnChange' => true,'rgxp'=>'date', 'doNotCopy'=>false, 'datepicker'=>true, 'tl_class'=>'w25 wizard'],
            'sql'               => "bigint(20) NULL"
        ],
        'addNotes'          => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment']['addNotes'],
            'exclude'           => true,
            'eval'              => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'notes'             => [
            'inputType'         => 'textarea',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment']['notes'],
            'exclude'           => true,
            'search'            => false,
            'filter'            => false,
            'sorting'           => false,
            'eval'              => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql'               => 'text NULL'
        ],
        'published'         => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment']['published'],
            'toggle'            => true,
            'filter'            => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_DESC,
            'eval'              => ['doNotCopy'=>true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'start'             => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment']['start'],
            'eval'              => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 clr wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'stop'              => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment']['stop'],
            'eval'              => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ]
    ]
];

class tl_dc_equipment extends Backend
{
    public LoggerInterface $logger;
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
                ->prepare("SELECT id FROM tl_dc_equipment WHERE alias=? AND id!=?")
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
