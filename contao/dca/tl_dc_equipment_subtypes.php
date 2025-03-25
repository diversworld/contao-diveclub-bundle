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
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\EquipmentHeaderCallback;
use Psr\Log\LoggerInterface;
use Contao\TemplateLoader;

/**
 * Table tl_dc_tanks
 */
$GLOBALS['TL_DCA']['tl_dc_equipment_subtypes'] = [
    'config'        => [
        'dataContainer'     => DC_Table::class,
        'ptable'            => 'tl_dc_equipment_types',
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
            'header_callback'   => [EquipmentHeaderCallback::class, '__invoke'],
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout'       => 'filter;sort,search,limit'
        ],
        'label'             => [
            'fields'            => ['title','manufacturer','model','size','status'],
            'label_callback' 	=> ['tl_dc_equipment_subtypes', 'formatLabel'],
            'showColumns'       => true,
            'format'            => '%s %s %s %s %s',
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
        'default'           => '{title_legend},title,alias,status;
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
            'foreignKey'        => 'tl_dc_equipment_types.title',
            'sql'               => "int(10) unsigned NOT NULL default 0",
            'relation'          => ['type' => 'belongsTo', 'load' => 'lazy'], // Typ anpassen, falls notwendig
        ],
        'tstamp'            => [
            'sql'               => "int(10) unsigned NOT NULL default 0"
        ],
        'title'             => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_subtypes']['title'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => true, 'maxlength'=>255, 'tl_class' => 'w33'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'alias'             => [
            'search'            => true,
            'inputType'         => 'text',
            'eval'              => ['rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w33'],
            'save_callback' => [
                ['tl_dc_equipment_subtypes', 'generateAlias']
            ],
            'sql'           => "varchar(255) BINARY NOT NULL default ''"
        ],
        'manufacturer'      => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_subtypes']['manufacturer'],
            'inputType'         => 'select',
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => ['tl_dc_equipment_subtypes', 'getManufacturers'],
            'eval'              => ['mandatory' => true, 'tl_class' => 'w25 clr'],
            'sql'               => "int(10) unsigned NOT NULL default 0",
        ],
        'model'             => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_subtypes']['model'],
            'inputType'         => 'text',
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'eval'              => array('mandatory' => false, 'tl_class' => 'w25'),
            'sql'               => "varchar(255) NOT NULL default ''",
        ],
        'color'             => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_subtypes']['color'],
            'inputType'         => 'text',
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'eval'              => array('mandatory' => false, 'tl_class' => 'w25'),
            'sql'               => "varchar(255) NOT NULL default ''",
        ],
        'size'              => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_subtypes']['size'],
            'inputType'         => 'select',
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => ['tl_dc_equipment_subtypes', 'getSizes'],
            'eval'              => ['mandatory' => false, 'tl_class' => 'w25'],
            'sql'               => "int(10) unsigned NOT NULL default 0",
        ],
        'serialNumber'      => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_subtypes']['serialNumber'],
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
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_subtypes']['buyDate'],
            'exclude'           => true,
            'search'            => true,
            'sorting'           => true,
            'filter'            => true,
            'flag'              => DataContainer::SORT_YEAR_DESC,
            'eval'              => ['submitOnChange' => true,'rgxp'=>'date', 'doNotCopy'=>false, 'datepicker'=>true, 'tl_class'=>'w25 wizard'],
            'sql'               => "bigint(20) NULL"
        ],
        'status'        => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_subtypes']['status'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options'           => &$GLOBALS['TL_LANG']['tl_dc_equipment_subtypes']['itemStatus'],
            'reference'         => &$GLOBALS['TL_LANG']['tl_dc_equipment_subtypes']['itemStatus'],
            'eval'              => ['includeBlankOption' => true, 'submitOnChange' => true, 'chosen'   => true, 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'addNotes'          => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_subtypes']['addNotes'],
            'exclude'           => true,
            'eval'              => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'notes'             => [
            'inputType'         => 'textarea',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_subtypes']['notes'],
            'exclude'           => true,
            'search'            => false,
            'filter'            => false,
            'sorting'           => false,
            'eval'              => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql'               => 'text NULL'
        ],
        'published'         => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_subtypes']['published'],
            'toggle'            => true,
            'filter'            => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_DESC,
            'eval'              => ['doNotCopy'=>true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'start'             => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_subtypes']['start'],
            'eval'              => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 clr wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'stop'              => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_subtypes']['stop'],
            'eval'              => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ]
    ]
];

class tl_dc_equipment_subtypes extends Backend
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
                ->prepare("SELECT id FROM tl_dc_equipment_subtypes WHERE alias=? AND id!=?")
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

    function getManufacturers()
    {
        return $this->getTemplateOptions('manufacturersFile');
    }

    public function getSizes()
    {
        return $this->getTemplateOptions('sizesFile');
    }

    private function getTemplateOptions($templateName): array
    {
        /// Templatepfad über Contao ermitteln
        $templatePath = $this->getTemplateFromConfig($templateName);

        // Überprüfen, ob die Datei existiert
        if (!$templatePath || !file_exists($templatePath)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['templateNotFound'], $templatePath));
        }

        // Dateiinhalt lesen
        $options = include $templatePath;

        if (!is_array($options)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['templateContent'], $options));
        }

        return $options;
    }

    function getTemplateFromConfig($templateName): string
    {
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $configArray = [];

        // Lade die erforderlichen Felder aus der Tabelle tl_dc_config
        $result = Database::getInstance()->execute("
            SELECT manufacturersFile, typesFile, subTypesFile, regulatorsFile, sizesFile
            FROM tl_dc_config
            LIMIT 1"
        );

        if ($result->numRows > 0) {
            // Für jedes Feld die UUID verarbeiten
            $files = [
                'manufacturersFile' => $result->manufacturersFile,
                'typesFile' => $result->typesFile,
                'subTypesFile' => $result->subTypesFile,
                'regulatorsFile' => $result->regulatorsFile,
                'sizesFile'     => $result->sizesFile,
            ];

            // UUIDs in Pfade umwandeln
            foreach ($files as $key => $uuid) {
                if (!empty($uuid)) {
                    $convertedUuid = StringUtil::binToUuid($uuid);
                    $fileModel = FilesModel::findByUuid($convertedUuid);

                    if ($fileModel !== null && file_exists($rootDir . '/' . $fileModel->path)) {
                        $configArray[$key] = $rootDir . '/' . $fileModel->path;
                    } else {
                        $configArray[$key] = null; // Datei nicht gefunden oder ungültige UUID
                    }
                } else {
                    $configArray[$key] = null; // Leerer Wert in der DB
                }
            }
        } else {
            throw new \RuntimeException('Keine Einträge in der Tabelle tl_dc_config gefunden.');
        }

        return $configArray[$templateName];
    }

    public function formatLabel(array $row, string $label, DataContainer $dc): string
    {
        // Hersteller-Name abrufen
        $manufacturers = $this->getManufacturers();
        $manufacturerName = $manufacturers[$row['manufacturer']] ?? 'Unbekannt';

        // Größen-Name abrufen
        $sizes = $this->getSizes();
        $sizeName = $sizes[$row['size']] ?? 'Unbekannt';
dump($row['status']);
        // Neues Label-Format mit Text-Werten
        return sprintf('%s %s %s - %s - %s', $manufacturerName, $row['model'], $sizeName, $row['title'], $GLOBALS['TL_LANG']['tl_dc_equipment_subtypes']['itemStatus'][$row['status']]);
    }
}
