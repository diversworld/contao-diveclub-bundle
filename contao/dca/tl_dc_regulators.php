<?php

declare(strict_types=1);

/**
 * This file is part of ContaoDiveclubBundle.
 *
 * (c) DiversWorld, Eckhard Becker 2025 <info@diversworld.eu>
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
use Contao\Input;
use Contao\CoreBundle\Monolog\ContaoContext;
use Diversworld\ContaoDiveclubBundle\DataContainer\DcTanks;
use Psr\Log\LoggerInterface;
use Contao\TemplateLoader;

/**
 * Table tl_dc_tanks
 */
$GLOBALS['TL_DCA']['tl_dc_regulators'] = [
    'config'        => [
        'dataContainer'     => DC_Table::class,
        'ctable'            => ['tl_dc_regulator_control'],
        'enableVersioning'  => true,
        'sql'               => [
            'keys'          => [
                'id'            => 'primary',
                'tstamp'        => 'index',
                'alias'         => 'index',
                'published,start,stop' => 'index'
            ]
        ],
    ],
    'list'          => [
        'sorting'           => [
            'mode'          => DataContainer::MODE_SORTABLE,
            'fields'        => ['title','alias','manufacturer','regModel1st','regModel2ndPri','regModel2ndSec','published'],
            'flag'          => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout'   => 'filter;sort,search,limit'
        ],
        'label'             => [
            'fields'            => ['title','manufacturer','regModel1st','regModel2ndPri','regModel2ndSec'],
            'showColumns'       => false,
            'format'            => '%s %s %s %s %s',
            'label_callback'    => ['tl_dc_regulators', 'customLabelCallback'],
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
            'children',
            'copy',
            'delete',
            'toggle',
            'show',
        ],
    ],
    'palettes'          => [
        '__selector__'      => ['addNotes'],
        'default'           => '{title_legend},title,alias;
                                {1stStage_legend},manufacturer,serialNumber1st,regModel1st;
                                {2ndstage_legend},serialNumber2ndPri,regModel2ndPri,serialNumber2ndSec,regModel2ndSec;
                                {notes_legend},addNotes;
                                {publish_legend},published,start,stop;'
    ],
    'subpalettes'       => [
        'addNotes'     => 'notes',
    ],
    'fields'            => [
        'id'                    => [
            'sql'               => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp'                => [
            'sql'               => "int(10) unsigned NOT NULL default 0"
        ],
        'title'                 => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['title'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => true, 'maxlength'=>255, 'tl_class' => 'w50'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'alias'                 => [
            'search'            => true,
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['alias'],
            'eval'              => ['rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w50'],
            'save_callback' => [
                ['tl_dc_regulators', 'generateAlias']
            ],
            'sql'           => "varchar(255) BINARY NOT NULL default ''"
        ],
        'manufacturer'          => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['manufacturer'],
            'inputType'         => 'select',
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => array('tl_dc_regulators', 'getManufacturers'),
            'eval'              => array('includeBlankOption' => true, 'submitOnChange' => true, 'mandatory' => true, 'tl_class' => 'w33 clr'),
            'sql'               => "varchar(255) NOT NULL default ''",
        ],
        'serialNumber1st'       => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['serialNumber1st'],
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25'],
            'sql'               => "varchar(50) NOT NULL default ''"
        ],
        'regModel1st'           => [
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
        'serialNumber2ndPri'    => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['serialNumber2ndPri'],
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => false, 'maxlength' => 50, 'tl_class' => 'w25 clr'],
            'sql'               => "varchar(50) NOT NULL default ''"
        ],
        'regModel2ndPri'        => [
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
        'serialNumber2ndSec'    => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['serialNumber2ndSec'],
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'eval'              => ['mandatory' => false, 'maxlength' => 50, 'tl_class' => 'w25'],
            'sql'               => "varchar(50) NOT NULL default ''"
        ],
        'regModel2ndSec'        => [
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
        'addNotes'              => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['addNotes'],
            'exclude'           => true,
            'eval'              => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql'       => ['type' => 'boolean', 'default' => false]
        ],
        'notes'                 => [
            'inputType'         => 'textarea',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['notes'],
            'exclude'           => true,
            'search'            => false,
            'filter'            => false,
            'sorting'           => false,
            'eval'              => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql'               => 'text NULL'
        ],
        'published'             => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['published'],
            'toggle'            => true,
            'filter'            => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_DESC,
            'eval'              => ['doNotCopy'=>true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'start'                 => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['start'],
            'eval'              => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 clr wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'stop'                  => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['stop'],
            'eval'              => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ]
    ]
];

class tl_dc_regulators extends Backend
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
        } elseif (preg_match('/^[1-9]\d*$/', $varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
        } elseif ($aliasExists($varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }
    public function getManufacturers()
    {
        return $this->getTemplateOptions('equipment_manufacturers');
    }

    public function getSizes()
    {
        return $this->getTemplateOptions('equipment_sizes');
    }

    private function getTemplateOptions($templateName)
    {
        $this->logger = System::getContainer()->get('monolog.logger.contao.general');
        // Templatepfad über Contao ermitteln
        $templatePath = TemplateLoader::getPath($templateName, 'html5');

        // Überprüfen, ob die Datei existiert
        if (!$templatePath || !file_exists($templatePath)) {
            $this->logger->error('Template file not found: ' . $templatePath);
            return [];
        }

        // Dateiinhalt lesen
        $content = file_get_contents($templatePath);

        $options = [];
        // Entferne PHP-Tags und wandle Daten in ein Array um
        $content = trim($content);
        $content = trim($content, '<?php');
        $content = trim($content, '?>');

        eval('$options = ' . $content . ';');

        if (!is_array($options)) {
            $this->logger->error('Failed to parse template content into an array: ' . $content);
            return [];
        }

        return $options;
    }
    public function getRegModels1st(DataContainer $dc): array
    {
        // Sicherstellen, dass ein aktiver Datensatz vorhanden ist
        if (!$dc->activeRecord || !$dc->activeRecord->manufacturer) {
            return [];
        }

        // Ermittle den aktuellen Typ aus dem aktiven Datensatz
        $manufacturer = $dc->activeRecord->manufacturer; // Aktueller Hersteller
        $models = $this->getTemplateOptions('regulator_data');

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
        $models = $this->getTemplateOptions('regulator_data');

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
        $args[1] = $manufacturers[$row['manufacturer']] ?? '-'; // Hersteller-Name einsetzen

        // Modelle für die erste und zweite Stufe basierend auf dem Hersteller laden
        $models = $this->getTemplateOptions('regulator_data');

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
