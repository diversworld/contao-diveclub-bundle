<?php

declare(strict_types=1);

use Contao\Backend;
use Contao\Controller;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Contao\TemplateLoader;
use Contao\ThemeModel;

$GLOBALS['TL_DCA']['tl_dc_equipment_types'] = [
    // Konfiguration
    'config'            => [
        'dataContainer'     => DC_Table::class,
        'ctable'            => ['tl_dc_equipment_subtypes'],
        'enableVersioning'  => true,
        'sql'               => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    // Listenansicht
    'list'              => [
        'sorting'           => [
            'mode'          => DataContainer::MODE_SORTED,
            'fields'        => ['types', 'subType', 'title', 'published'],
            'flag'          => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout'   => 'filter;search,limit',
        ],
        'label'             => [
            'fields'        => ['types', 'subType'],
            'label_callback'=> ['tl_dc_equipment_types', 'customLabelCallback'],
        ],
        'global_operations' => [
            'all' => [
                'href'      => 'act=select',
                'class'     => 'header_edit_all',
                'attributes'=> 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations'        => [
            'edit',
            'children',
            'copy',
            'delete',
            'show',
            'toggle',
        ],
    ],
    // Palettes-Konfiguration
    'palettes'          => [
        'default'   => '{title_legend},title,alias;
                        {types_legend},types,subType;
                        {notes_legend},addNotes;
                        {publish_legend},published,start,stop;',
    ],
    'subpalettes'       => [
        'addNotes'          => 'notes',
    ],
    // Felder
    'fields'            => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp'            => [
            'sql'           => "int(10) unsigned NOT NULL default 0"
        ],
        'alias'             => [
            'search'        => true,
            'inputType'     => 'text',
            'eval'          => ['rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w33'],
            'save_callback' => [['tl_dc_equipment_types', 'generateAlias']],
            'sql'           => "varchar(255) BINARY NOT NULL default ''"
        ],
        'title'             => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_types']['title'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => array('tl_dc_equipment_types', 'getTypes'),
            'flag'              => DataContainer::SORT_INITIAL_LETTERS_ASC,
            'eval'              => array('includeBlankOption' => true, 'submitOnChange' => true, 'mandatory' => true, 'tl_class' => 'w25 clr'),
            'sql'               => "int(10) unsigned NOT NULL default 0",
        ],/*
        'types'             => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_types']['types'],
            'inputType'         => 'select',
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => array('tl_dc_equipment_types', 'getTypes'),
            'eval'              => array('includeBlankOption' => true, 'submitOnChange' => true, 'mandatory' => true, 'tl_class' => 'w25 clr'),
            'sql'               => "int(10) unsigned NOT NULL default 0",
        ],*/
        'subType' => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_types']['subType'],
            'exclude'           => true,
            'options_callback'  => ['tl_dc_equipment_types', 'getSubTypes'],
            'eval'              => ['includeBlankOption' => true,'mandatory' => false,'tl_class' => 'w50',],
            'sql'               => "int(10) unsigned NOT NULL default 0",
        ],
        'addNotes'          => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_types']['addNotes'],
            'exclude'           => true,
            'eval'              => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'notes'             => [
            'inputType'         => 'textarea',
            'exclude'           => true,
            'search'            => false,
            'filter'            => true,
            'sorting'           => false,
            'eval'              => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql'               => 'text NULL'
        ],
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

class tl_dc_equipment_types extends Backend
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
        $aliasExists = static function (string $alias) use ($dc): bool {
            $result = Database::getInstance()
                ->prepare("SELECT id FROM tl_dc_equipment_types WHERE alias=? AND id!=?")
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
        }
        elseif (preg_match('/^[1-9]\d*$/', $varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
        }
        elseif ($aliasExists($varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }

    /**
     * Liefert die Equipment-Typen (Hauptauswahl, z.B. "Anzüge", "Atemregler")
     */
    public function getTypes(): array
    {
        return $this->getTemplateOptions('typesFile');//'dc_equipment_types');
    }

    /**
     * Liefert die Subtypen (abhängig vom Typ, z.B. "shorty", "trocken")
     */
    public function getSubTypes(DataContainer $dc): array
    {
        if (!$dc->activeRecord || !$dc->activeRecord->title) {
            return [];
        }
        $types = $this->getTemplateOptions('subTypesFile');
        return $types[$dc->activeRecord->title] ?? [];
    }

    /**
     * Lädt die Templates und konvertiert sie in ein Array
     */
    private function getTemplateOptions(string $templateName): array
    {
        // Templatepfad über Contao ermitteln
        $templatePath = $this->getTemplateFromConfig($templateName);

        // Überprüfen, ob die Datei existiert
        if (!$templatePath || !file_exists($templatePath)) {
            throw new \Exception(sprintf('Template "%s" not found or not readable', $templateName));
        }

        $options = [];
        //$content = file_get_contents($templatePath);

        $options = include $templatePath;

        if (!is_array($options)) {
            throw new \Exception(sprintf('Invalid template content in file: %s', $templatePath));
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
                'sizesFile' => $result->sizesFile,
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

    /**
     * Erzeugt ein Label für die Ausgabe in der Listenansicht
     */
    public function customLabelCallback(array $row, string $label, DataContainer $dc, array $args = null): string
    {
        $types = $this->getTypes();
        $subTypes = $this->getTemplateOptions('subTypesFile');

        $typeLabel = $types[$row['types']] ?? $row['types'];
        $subTypeLabel = $subTypes[$row['types']][$row['subType']] ?? $row['subType'];

        return sprintf('%s / %s', $typeLabel, $subTypeLabel);
    }
}
