<?php

use Contao\Controller;
use Contao\Database;
use Contao\DataContainer;
use Contao\Backend;
use Contao\DC_Table;
use Contao\System;
use Psr\Log\LoggerInterface;

$GLOBALS['TL_DCA']['tl_dc_config'] = [
    'config'        => [
        'dataContainer'         => DC_Table::class,
        'enableVersioning'      => true,
        'sql'                   => [
            'keys'                  => [
                'id'                    => 'primary',
                'tstamp'                => 'index',
                'alias'                 => 'index',
            ],
        ],
    ],
    'list'          => [
        'sorting'           => [
            'mode'              => DataContainer::MODE_SORTED, // Sortieren nach einem Feld
            'fields'            => ['title','alias'], // Sortierfeld: Template-Name
            'flag'              => DataContainer::SORT_ASC, // Sortierung aufsteigend
            'panelLayout'       => 'filter;sort,search,limit', // Filter, Suche etc.
        ],
        'label'             => [
            'fields'            => ['title', 'alias'], // Zeigt diese Felder in der Liste an
            'showColumns'       => true,
            'format'            => '%s (%s)', // Ausgabeformat: Template-Name (Ordner)
        ],
        'global_operations' => [
            'all'               => [
                'href'              => 'act=select',
                'class'             => 'header_edit_all',
                'attributes'        => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations'        => [
            'edit',
            'copy',
            'delete',
            'toggle',
            'show',
        ],
    ],
    'palettes'      => [
        '__selector__'      => ['addManufacturer', 'addRegulators', 'addEquipment', 'addSizes',],
        'default'           => '{title_legend},title,alias;
                                {manufacturer_legend},addManufacturer;
                                {equipment_legend},addEquipment;
                                {sizes_legend},addSizes;
                                {types_legend},addTypes;
                                {regulator_legend},addRegulators;
                                {publish_legend},published,start,stop;'
    ],
    'subpalettes'   => [
        'addManufacturer'   => 'manufacturersFile',
        'addEquipment'      => 'typesFile,subTypesFile',
        'addSizes'          => 'sizesFile',
        'addRegulators'     => 'regulatorsFile',
    ],
    'fields'        => [
        'id'                    => [
            'sql'                   => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp'                => [
            'sql'                   => "int(10) unsigned NOT NULL default 0",
        ],
        'title'                 => [
            'inputType'             => 'text',
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_equipment']['title'],
            'exclude'               => true,
            'search'                => true,
            'filter'                => true,
            'sorting'               => true,
            'flag'                  => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'                  => ['mandatory' => true, 'maxlength'=>255, 'tl_class' => 'w50'],
            'sql'                   => "varchar(255) NOT NULL default ''"
        ],
        'alias'                 => [
            'search'                => true,
            'inputType'             => 'text',
            'eval'                  => ['rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w50'],
            'save_callback'         => [
                        ['tl_dc_config', 'generateAlias']
            ],
            'sql'                   => "varchar(255) BINARY NOT NULL default ''"
        ],
        'addManufacturer'       => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_config']['addManufacturer'],
            'exclude'               => true,
            'inputType'             => 'checkbox',
            'eval'                  => ['submitOnChange' => true],
            'sql'                   => ['type' => 'boolean', 'default' => false]
        ],
        'addEquipment'          => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_config']['addEquipment'],
            'exclude'               => true,
            'inputType'             => 'checkbox',
            'eval'                  => ['submitOnChange' => true],
            'sql'                   => ['type' => 'boolean', 'default' => false]
        ],
        'addSizes'              => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_config']['addSizes'],
            'exclude'               => true,
            'inputType'             => 'checkbox',
            'eval'                  => ['submitOnChange' => true],
            'sql'                   => ['type' => 'boolean', 'default' => false]
        ],
        'addRegulators'         => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_config']['addRegulators'],
            'exclude'               => true,
            'inputType'             => 'checkbox',
            'eval'                  => ['submitOnChange' => true],
            'sql'                   => ['type' => 'boolean', 'default' => false]
        ],
        'manufacturersFile' => array
        (
            'inputType'               => 'fileTree',
            'eval'                    => array('fieldType'=>'radio', 'files' => true, 'tl_class'=>'33clr'),
            'sql'                     => "binary(16) NULL"
        ),
        'sizesFile' => array
        (
            'inputType'               => 'fileTree',
            'eval'                    => array('fieldType'=>'radio', 'files' => true, 'tl_class'=>'w33clr'),
            'sql'                     => "binary(16) NULL"
        ),
        'typesFile' => array
        (
            'inputType'               => 'fileTree',
            'eval'                    => array('fieldType'=>'radio', 'files' => true, 'tl_class'=>'w33clr'),
            'sql'                     => "binary(16) NULL"
        ),
        'subTypesFile' => array
        (
            'inputType'               => 'fileTree',
            'eval'                    => array('fieldType'=>'radio', 'files' => true, 'tl_class'=>'w33clr'),
            'sql'                     => "binary(16) NULL"
        ),
        'regulatorsFile' => array
        (
            'inputType'               => 'fileTree',
            'eval'                    => array('fieldType'=>'radio', 'files' => true, 'tl_class'=>'w33clr'),
            'sql'                     => "binary(16) NULL"
        ),
        'published'         => [
            'toggle'            => true,
            'filter'            => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_DESC,
            'inputType'         => 'checkbox',
            'eval'              => ['doNotCopy'=>true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'start'             => [
            'inputType'         => 'text',
            'eval'              => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 clr wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'stop'              => [
            'inputType'         => 'text',
            'eval'              => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ]
    ],
];

class tl_dc_config extends Backend
{
    public LoggerInterface $logger;
    public function generateAlias(mixed $varValue, DataContainer $dc): mixed
    {
        $aliasExists = static function (string $alias) use ($dc): bool {
            $result = Database::getInstance()
                ->prepare("SELECT id FROM tl_dc_config WHERE alias=? AND id!=?")
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

    /**
     * Return all event templates as array
     * @param object
     * @return array
     */
    public function getDcTemplates(): array
    {
//        $this->logger = System::getContainer()->get('monolog.logger.contao.general');
//        $this->logger->info('getDcTemplates: '. print_r($this->getTemplateGroup('dc_')));
        return $this->getTemplateGroup('dc_');
    }

    /**
     * Return all event templates as array
     * @param object
     * @return array
     */
    public function getManufacturerTemplates()
    {
        return $this->getTemplateGroup('dc_equipment_manufacturers');
    }

    /**
     * Return all event templates as array
     * @param object
     * @return array
     */
    public function getEquipmentTemplates()
    {
        return $this->getTemplateGroup('dc_equipment_');
    }

    /**
     * Return all event templates as array
     * @param object
     * @return array
     */
    public function getSizesTemplates()
    {
        return $this->getTemplateGroup('dc_equipment_sizes_');
    }
    /**
     * Return all event templates as array
     * @param object
     * @return array
     */
    public function getRegTemplates()
    {
        return $this->getTemplateGroup('dc_regulator_');
    }

    /**
     * Return all event templates as array
     * @param object
     * @return array
     */
    public function getTypesTemplates()
    {
        return $this->getTemplateGroup('dc_equipment_types');
    }
}
