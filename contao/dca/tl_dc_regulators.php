<?php

declare(strict_types=1);

/*
 * This file is part of ContaoDiveclubBundle.
 *
 * (c) DiversWorld 2024 <eckhard@diversworld.eu>
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
                'pid'           => 'index',
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
                                {1stStage_legend},serialNumber1st,manufacturer;
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
        'pid'                   => [
            'foreignKey'        => 'tl_dc_regulators.title',
            'sql'               => "int(10) unsigned NOT NULL default 0",
            'relation'          => ['type' => 'belongsTo', 'load' => 'lazy'], // Typ anpassen, falls notwendig
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
            'eval'              => array('submitOnChange' => true, 'mandatory' => true, 'tl_class' => 'w33 clr'),
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
            'options_callback'  => ['tl_dc_regulators', 'getRegModels'],
            'eval'              => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
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
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['regModel'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => ['tl_dc_regulators', 'getRegModels'],
            'eval'              => ['submitOnChange' => true, 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
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
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_regulators']['regModel2ndPri'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => ['tl_dc_regulators', 'getRegModels'],
            'eval'              => ['submitOnChange' => true, 'mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w25'],
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
    public function getRegModels(DataContainer $dc): array
    {
        // Sicherstellen, dass ein aktiver Datensatz vorhanden ist
        if (!$dc->activeRecord) {
            return [];
        }

        // Ermittle den aktuellen Typ aus dem aktiven Datensatz
        $currentType = $dc->activeRecord->manufacturer;

        $subTypes = $this->getTemplateOptions('equipment_regulatermodels');

        // Prüfen, ob für den aktuellen Typ Subtypen definiert wurden
        if (!isset($subTypes[$currentType]) || !is_array($subTypes[$currentType])) {
            // Keine passenden Subtypen gefunden -> leere Liste zurückgeben
            return [];
        }
        // Nur die relevanten Subtypen für diesen Typ zurückgeben
        return $subTypes[$currentType];
    }
}
