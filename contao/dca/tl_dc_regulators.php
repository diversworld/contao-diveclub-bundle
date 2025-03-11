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
use Contao\Controller;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\FilesModel;
use Contao\StringUtil;
use Contao\System;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\EventListener\Widget\HttpUrlListener;
use Contao\TemplateLoader;
use Contao\ThemeModel;
use Diversworld\ContaoDiveclubBundle\DataContainer\DcCheckProposal;

/**
 * Table tl_dc_regulators
 */
$GLOBALS['TL_DCA']['tl_dc_regulators'] = [
    'config'            => [
        'dataContainer'     => DC_Table::class,
        'ctable'            => ['tl_dc_regulator_control'],
        'enableVersioning'  => true,
        'sql'               => [
            'keys' => [
                'id'        => 'primary',
                'tstamp'    => 'index',
                'alias'     => 'index',
                'published,start,stop' => 'index'
            ]
        ],
    ],
    'list'              => [
        'sorting'           => [
            'mode'          => DataContainer::MODE_SORTABLE,
            'fields'        => ['title','alias','published'],
            'flag'          => DataContainer::SORT_ASC,
            'panelLayout'   => 'filter;sort,search,limit'
        ],
        'label'             => [
            'fields' => ['title','regModel1st','regModel2ndPri','regModel2ndSec'],
            'format' => '%s - %s %s %s',
            'label_callback' => ['tl_dc_regulators', 'customLabelCallback']
        ],
        'global_operations' => [
            'all' => [
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations'        => [
            'edit',
            'children',
            'copy',
            'delete',
            'show',
            'toggle'
        ]
    ],
    'palettes'          => [
        '__selector__'      => ['addArticleInfo'],
        'default'           => '{title_legend},title,alias;
                                {1stStage_legend},manufacturer,serialNumber1st,regModel1st;
                                {2ndstage_legend},serialNumber2ndPri,regModel2ndPri,serialNumber2ndSec,regModel2ndSec;
                                {notes_legend},addNotes;
                                {publish_legend},published,start,stop;'
    ],
    'subpalettes'       => [
        'addNotes'          => 'notes',
    ],
    'fields'            => [
        'id'                => [
            'sql'           => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp'            => [
            'sql'           => "int(10) unsigned NOT NULL default 0"
        ],
        'title'             => [
            'inputType'     => 'text',
            'label'         => &$GLOBALS['TL_LANG']['tl_dc_regulators']['title'],
            'exclude'       => true,
            'search'        => true,
            'filter'        => true,
            'sorting'       => true,
            'flag'          => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'          => ['mandatory' => true, 'maxlength' => 25, 'tl_class' => 'w33'],
            'sql'           => "varchar(255) NOT NULL default ''"
        ],
        'alias'             => [
            'search'        => true,
            'inputType'     => 'text',
            'eval'          => ['rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w33'],
            'save_callback' => [['tl_dc_regulators', 'generateAlias']],
            'sql'           => "varchar(255) BINARY NOT NULL default ''"
        ],
        'manufacturer'      => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['manufacturer'],
            'inputType'         => 'select',
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => array('tl_dc_regulators', 'getManufacturers'),
            'eval'              => array('includeBlankOption' => true, 'submitOnChange' => true, 'mandatory' => true, 'tl_class' => 'w25 clr'),
            'sql'               => "varchar(255) NOT NULL default ''",
        ],
        'serialNumber1st'   => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['serialNumber1st'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25'],
            'sql'               => "varchar(50) NOT NULL default ''"
        ],
        'regModel1st'       => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['regModel1st'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => ['tl_dc_regulators', 'getRegModels1st'],
            'eval'              => ['includeBlankOption' => true, 'mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'serialNumber2ndPri'=> [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['serialNumber2ndPri'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => false, 'maxlength' => 50, 'tl_class' => 'w25 clr'],
            'sql'               => "varchar(50) NOT NULL default ''"
        ],
        'regModel2ndPri'    => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['regModel2ndPri'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => ['tl_dc_regulators', 'getRegModels2nd'],
            'eval'              => ['includeBlankOption' => true, 'submitOnChange' => true, 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'serialNumber2ndSec'=> [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['serialNumber2ndSec'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'eval'              => ['mandatory' => false, 'maxlength' => 50, 'tl_class' => 'w25'],
            'sql'               => "varchar(50) NOT NULL default ''"
        ],
        'regModel2ndSec'    => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['regModel2ndSec'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => ['tl_dc_regulators', 'getRegModels2nd'],
            'eval'              => ['includeBlankOption' => true,'submitOnChange' => true, 'mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'addNotes'          => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['addNotes'],
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
    ]
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property DcCheckProposal $dcCheckProposal
 *
 * @internal
 */
class tl_dc_regulators extends Backend
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
                ->prepare("SELECT id FROM tl_dc_regulators WHERE alias=? AND id!=?")
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

    public function getManufacturers()
    {
        return $this->getTemplateOptions('manufacturersFile');
    }

    private function getTemplateOptions($templateName)
    {
        // Templatepfad über Contao ermitteln
        $templatePath = $this->getTemplateFromConfig($templateName);

        // Überprüfen, ob die Datei existiert
        if (!$templatePath || !file_exists($templatePath)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['templateNotFound'], $templatePath));
        }

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

    public function getRegModels1st(DataContainer $dc): array
    {
        // Sicherstellen, dass ein aktiver Datensatz vorhanden ist
        if (!$dc->activeRecord || !$dc->activeRecord->manufacturer) {
            return [];
        }

        // Ermittle den aktuellen Typ aus dem aktiven Datensatz
        $manufacturer = $dc->activeRecord->manufacturer; // Aktueller Hersteller
        $models = $this->getTemplateOptions('regulatorsFile');

        // Prüfen, ob der Hersteller existiert und Modelle für die erste Stufe definiert sind
        if (!isset($models[$manufacturer]['regModel1st']) || !is_array($models[$manufacturer]['regModel1st'])) {
            return [];
        }

        // Rückgabe der Modelle für die erste Stufe
        return $models[$manufacturer]['regModel1st'];
    }

    public function getRegModels2nd(DataContainer $dc): array
    {
        if (!$dc->activeRecord || !$dc->activeRecord->manufacturer) {
            return [];
        }

        $manufacturer = $dc->activeRecord->manufacturer; // Aktueller Hersteller
        $models = $this->getTemplateOptions('regulatorsFile');

        // Prüfen, ob der Hersteller existiert und Modelle für die zweite Stufe definiert sind
        if (!isset($models[$manufacturer]['regModel2nd']) || !is_array($models[$manufacturer]['regModel2nd'])) {
            return [];
        }

        // Rückgabe der Modelle für die zweite Stufe
        return $models[$manufacturer]['regModel2nd'];
    }

    public function customLabelCallback(array $row, string $label, DataContainer $dc = null, array $args = null): array
    {
        // Hersteller auslesen
        $manufacturer = $row['manufacturer'];
        $manufacturers = $this->getManufacturers();

        $args[1] = $manufacturers[$row['manufacturer']] ?? '-'; // Hersteller-Name einsetzen

        // Modelle für die erste und zweite Stufe basierend auf dem Hersteller laden
        $models = $this->getTemplateOptions('regulatorsFile');

        // Namen der Modelle statt der Indexwerte benutzen
        if (isset($models[$manufacturer]['regModel1st'][$row['regModel1st']])) {
            $args[2] = $models[$manufacturer]['regModel1st'][$row['regModel1st']];
        } else {
            $args[2] = '-'; // Fallback, falls nichts gefunden wird
        }

        if (isset($models[$manufacturer]['regModel2nd'][$row['regModel2ndPri']])) {
            $args[3] = $models[$manufacturer]['regModel2nd'][$row['regModel2ndPri']];
        } else {
            $args[3] = '-'; // Fallback
        }

        if (isset($models[$manufacturer]['regModel2nd'][$row['regModel2ndSec']])) {
            $args[4] = $models[$manufacturer]['regModel2nd'][$row['regModel2ndSec']];
        } else {
            $args[4] = '-'; // Fallback
        }

        return $args;
    }
}
