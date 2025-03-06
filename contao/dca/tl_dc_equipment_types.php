<?php

declare(strict_types=1);

use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use Contao\TemplateLoader;

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
            'fields'        => ['title'],
            'flag'          => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout'   => 'filter;search,limit',
        ],
        'label'             => [
            'fields'        => ['title', 'subType'],
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
            'edit' => [
                'href'  => 'act=edit',
                'icon'  => 'edit.svg',
            ],
            'children',
            'copy' => [
                'href'  => 'act=copy',
                'icon'  => 'copy.svg',
            ],
            'delete' => [
                'href'  => 'act=delete',
                'icon'  => 'delete.svg',
            ],
            'show' => [
                'href'  => 'act=show',
                'icon'  => 'show.svg',
            ],
        ],
    ],
    // Palettes-Konfiguration
    'palettes'          => [
        'default' => '{title_legend},title,subType;
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
            'save_callback' => [['tl_dc_regulators', 'generateAlias']],
            'sql'           => "varchar(255) BINARY NOT NULL default ''"
        ],
        'title' => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_types']['title'],
            'exclude'           => true,
            'options_callback'  => ['tl_dc_equipment_types', 'getTypes'],
            'eval'              => [
                'includeBlankOption' => true,
                'mandatory'          => true,
                'tl_class'           => 'w50',
            ],
            'sql'               => "int(10) unsigned NOT NULL default 0",
        ],
        'subType' => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_types']['subType'],
            'exclude'           => true,
            'options_callback'  => ['tl_dc_equipment_types', 'getSubTypes'],
            'eval'              => [
                'includeBlankOption' => true,
                'mandatory'          => false,
                'tl_class'           => 'w50',
            ],
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
        return $this->getTemplateOptions('dc_equipment_types');
    }

    /**
     * Liefert die Subtypen (abhängig vom Typ, z.B. "shorty", "trocken")
     */
    public function getSubTypes(DataContainer $dc): array
    {
        if (!$dc->activeRecord || !$dc->activeRecord->title) {
            return [];
        }

        $types = $this->getTemplateOptions('dc_equipment_subtypes');
        return $types[$dc->activeRecord->title] ?? [];
    }

    /**
     * Lädt die Templates und konvertiert sie in ein Array
     */
    private function getTemplateOptions(string $templateName): array
    {
        // Templatepfad über Contao ermitteln
        $templatePath = TemplateLoader::getPath($templateName, 'html5');

        // Überprüfen, ob die Datei existiert
        if (!$templatePath || !file_exists($templatePath)) {
            throw new \Exception(sprintf('Template "%s" not found or not readable', $templateName));
        }

        // Templateinhalt auswerten
        $options = include $templatePath;
        if (!is_array($options)) {
            throw new \Exception(sprintf('Invalid template content in file: %s', $templatePath));
        }

        return $options;
    }

    /**
     * Erzeugt ein Label für die Ausgabe in der Listenansicht
     */
    public function customLabelCallback(array $row, string $label, DataContainer $dc, array $args = null): string
    {
        $types = $this->getTypes();
        $subTypes = $this->getTemplateOptions('dc_equipment_subtypes');

        $typeLabel = $types[$row['title']] ?? $row['title'];
        $subTypeLabel = $subTypes[$row['title']][$row['subType']] ?? $row['subType'];

        return sprintf('%s / %s', $typeLabel, $subTypeLabel);
    }
}
