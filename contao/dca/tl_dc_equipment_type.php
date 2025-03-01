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
use Contao\BackendTemplate;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\FrontendTemplate;
use Contao\System;
use Contao\Input;
use Contao\CoreBundle\Monolog\ContaoContext;
use Diversworld\ContaoDiveclubBundle\DataContainer\DcTanks;
use Psr\Log\LoggerInterface;
use Contao\TemplateLoader;

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
            'label_callback' => ['tl_dc_equipment_type', 'subTypeLabel'], // Callback zum Anpassen
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
            'options_callback'  => ['tl_dc_equipment_type', 'getTypes'],
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
            'options_callback'  => ['tl_dc_equipment_type', 'getSubTypes'],
            'eval'              => ['mandatory' => false, 'tl_class' => 'w33'],
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

    public function getTypes():array
    {
        return $this->getTemplateOptions('dc_equipment_types');
    }

    public function getSubTypes(DataContainer $dc): array
    {
        // Sicherstellen, dass ein aktiver Datensatz vorhanden ist
        if (!$dc->activeRecord) {
            return [];
        }

        // Ermittle den aktuellen Typ aus dem aktiven Datensatz
        $currentType = $dc->activeRecord->title;

        $subTypes = $this->getTemplateOptions('dc_equipment_subTypes');

        // Prüfen, ob für den aktuellen Typ Subtypen definiert wurden
        if (!isset($subTypes[$currentType]) || !is_array($subTypes[$currentType])) {
            // Keine passenden Subtypen gefunden -> leere Liste zurückgeben
            return [];
        }

        // Nur die relevanten Subtypen für diesen Typ zurückgeben
        return $subTypes[$currentType];
    }

    private function getTemplateOptions($templateName)
    {
        $this->logger = System::getContainer()->get('monolog.logger.contao.general');
        // Zuerst nach dem Template im Root-Template-Verzeichnis suchen
        $rootTemplatePath = System::getContainer()->getParameter('kernel.project_dir') . '/templates/diveclub/' . $templateName . '.html5';
        $this->logger->debug('Root template path: ' . $rootTemplatePath);

        if (is_readable($rootTemplatePath)) {
            $this->logger->debug('Template is readable.');
            return $this->parseTemplateFile($rootTemplatePath);
        } else {
            $this->logger->error('Template not found or not readable: ' . $rootTemplatePath);
        }

        // Falls nicht im Root-Template-Verzeichnis, Prüfung im Modul-Verzeichnis
        $moduleTemplatePath = TemplateLoader::getPath($templateName, 'html5');

        if ($moduleTemplatePath && file_exists($moduleTemplatePath)) {
            $this->logger->debug('Template found in module directory: ' . $moduleTemplatePath);

            return $this->parseTemplateFile($moduleTemplatePath);
        }

        // Wenn keine Datei gefunden wurde, Fehlermeldung ausgeben
        $this->logger->error('Template not found: ' . $templateName);
        throw new Exception(sprintf('Template not found: %s', $templateName));
    }

    private function parseTemplateFile(string $filePath): array
    {
        // Dateiinhalt lesen
        $content = file_get_contents($filePath);

        // Entferne PHP-Tags und wandle Inhalt in ein Array um
        $content = trim($content);
        $content = trim($content, '<?=');
        $content = trim($content, '?>');

        // Eval-Schutz gegen fehlerhafte Inhalte
        $options = [];
        eval('$options = ' . $content . ';');

        if (!is_array($options)) {
            throw new Exception(sprintf('Invalid template content in file: %s', $filePath));
        }

        return $options;
    }

    public function subTypeLabel(array $row, string $label, DataContainer $dc = null): string
    {
        // Lade die Subtypen aus der Template-Datei
        $subTypes = $this->getTemplateOptions('dc_equipment_subTypes');

        // Ermittle den aktuellen Subtypen-Text basierend auf dem gespeicherten Typ und Subtyp
        $currentType = $row['title']; // Titel/Typ aus der Datenbankzeile
        $subTypeId = $row['subType']; // Subtype-ID aus der Datenbankzeile

        // Standardwert, falls keine Zuordnung gefunden wird
        $subTypeName = $subTypeId;

        // Überprüfen, ob der Titel/Subtype im Array existiert
        if (isset($subTypes[$currentType]) && isset($subTypes[$currentType][$subTypeId])) {
            $subTypeName = $subTypes[$currentType][$subTypeId];
        }

        // Label als Kombination aus Titel und Subtype-Name zurückgeben
        return sprintf('%s', $subTypeName);
    }

}
