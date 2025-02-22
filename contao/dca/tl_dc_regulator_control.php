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
use Psr\Log\LoggerInterface;
use Contao\TemplateLoader;

/**
 * Table tl_dc_regulator_control
 */
$GLOBALS['TL_DCA']['tl_dc_regulator_control'] = [
    'config'        => [
        'dataContainer'     => DC_Table::class,
        'ptable'            => 'tl_dc_regulators',
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
    'list'                  => [
        'sorting'               => [
            'mode'                  => DataContainer::MODE_PARENT,
            'fields'                => ['title','alias','published'],
            'headerfields'         => ['title','alias','start','stop'],
            'flag'                  => DataContainer::SORT_ASC,
            'panelLayout'           => 'filter;sort,search,limit'
        ],
        'label'                 => [
            'fields'                => ['title','midPressurePre','inhalePressurePre','exhalePressurePre','midPressurePost','inhalePressurePost','exhalePressurePost'],
            'format'                => '%s: Vorher MD %s bar EAW %s AAW %s - Nachher MD %s bar EAW %s AAW %s',
        ],
        'global_operations'     => [
            'all'                   => [
                'href'                  => 'act=select',
                'class'                 => 'header_edit_all',
                'attributes'            => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations'            => [
            'edit',
            'copy',
            'delete',
            'toggle',
            'show',
        ],
    ],
    'palettes'              => [
        '__selector__'      => ['addNotes'],
        'default'           => '{title_legend},title,alias;
                                {pre_legend},midPreussurePre,inhalePressurePre,exhalePressurePre;
                                {post_legend},midPreussurePost,inhalePressurePost,exhalePressurePost;
                                {nextCheck_legend},nextCheckDate;
                                {notes_legend},addNotes;
                                {publish_legend},published,start,stop;'
    ],
    'subpalettes'           => [
        'addNotes'          => 'notes',
    ],
    'fields'                => [
        'id'                    => [
            'sql'                   => "int(10) unsigned NOT NULL auto_increment"
        ],
        'pid'                   => [
            'foreignKey'            => 'tl_dc_regulators.title',
            'sql'                   => "int(10) unsigned NOT NULL default 0",
            'relation'              => ['type' => 'belongsTo', 'load' => 'lazy'], // Typ anpassen, falls notwendig
        ],
        'tstamp'                => [
            'sql'                   => "int(10) unsigned NOT NULL default 0"
        ],
        'title'                 => [
            'inputType'             => 'text',
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['actualCheckDate'],
            'exclude'               => true,
            'search'                => true,
            'filter'                => true,
            'sorting'               => true,
            'flag'                  => DataContainer::SORT_YEAR_DESC,
            'eval'                  => ['submitOnChange' => true, 'rgxp'=>'date', 'mandatory'=>false, 'doNotCopy'=>true, 'datepicker'=>true, 'tl_class'=>'w33 wizard'],
            'sql'                   => "varchar(10) NOT NULL default ''"
        ],
        'alias'                 => [
            'search'                => true,
            'inputType'             => 'text',
            'eval'                  => ['rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w50'],
            'save_callback'         => [
                ['tl_dc_regulator_control', 'generateAlias']
            ],
            'sql'           => "varchar(255) BINARY NOT NULL default ''"
        ],
        'midPreussurePre'       => [
            'inputType'             => 'text',
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['midPreussurePre'],
            'search'                => true,
            'filter'                => true,
            'sorting'               => true,
            'eval'                  => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25 clr'],
            'sql'                   => "varchar(50) NOT NULL default ''"
        ],
        'inhalePressurePre'     => [
            'inputType'             => 'text',
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['inhalePressurePre'],
            'search'                => true,
            'filter'                => true,
            'sorting'               => true,
            'eval'                  => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25'],
            'sql'                   => "varchar(50) NOT NULL default ''"
        ],
        'exhalePressurePre'     => [
            'inputType'             => 'text',
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['exhalePressurePre'],
            'search'                => true,
            'filter'                => true,
            'sorting'               => true,
            'eval'                  => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25'],
            'sql'                   => "varchar(50) NOT NULL default ''"
        ],
        'midPreussurePost'      => [
            'inputType'             => 'text',
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['midPreussurePost'],
            'search'                => true,
            'filter'                => true,
            'sorting'               => true,
            'eval'                  => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25 clr'],
            'sql'                   => "varchar(50) NOT NULL default ''"
        ],
        'inhalePressurePost'    => [
            'inputType'             => 'text',
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['inhalePressurePost'],
            'search'                => true,
            'filter'                => true,
            'sorting'               => true,
            'flag'                  => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'                  => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25'],
            'sql'                   => "varchar(50) NOT NULL default ''"
        ],
        'exhalePressurePost'    => [
            'inputType'             => 'text',
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['exhalePressurePost'],
            'search'                => true,
            'filter'                => true,
            'sorting'               => true,
            'flag'                  => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'                  => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25'],
            'sql'                   => "varchar(50) NOT NULL default ''"
        ],
        'nextCheckDate'         => [
            'inputType'             => 'text',
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['nextCheckDate'],
            'sorting'               => true,
            'filter'                => true,
            'flag'                  => DataContainer::SORT_YEAR_DESC,
            'eval'                  => ['submitOnChange' => true,'rgxp'=>'date', 'doNotCopy'=>false, 'datepicker'=>true, 'tl_class'=>'w33 wizard'],
            'sql'                   => "varchar(10) NOT NULL default ''"
        ],
        'addNotes'              => [
            'inputType'             => 'checkbox',
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['addNotes'],
            'exclude'               => true,
            'eval'                  => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql'                   => ['type' => 'boolean', 'default' => false]
        ],
        'notes'                 => [
            'inputType'             => 'textarea',
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['notes'],
            'exclude'               => true,
            'search'                => false,
            'filter'                => false,
            'sorting'               => false,
            'eval'                  => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql'                   => 'text NULL'
        ],
        'published'             => [
            'inputType'             => 'checkbox',
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['published'],
            'toggle'                => true,
            'filter'                => true,
            'flag'                  => DataContainer::SORT_INITIAL_LETTER_DESC,
            'eval'                  => ['doNotCopy'=>true, 'tl_class' => 'w50'],
            'sql'                   => ['type' => 'boolean', 'default' => false]
        ],
        'start'                 => [
            'inputType'             => 'text',
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['start'],
            'eval'                  => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 clr wizard'],
            'sql'                   => "varchar(10) NOT NULL default ''"
        ],
        'stop'                  => [
            'inputType'             => 'text',
            'label'                 => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['stop'],
            'eval'                  => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'],
            'sql'                   => "varchar(10) NOT NULL default ''"
        ]
    ]
];

class tl_dc_regulator_control extends Backend
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
                ->prepare("SELECT id FROM tl_dc_regulator_control WHERE alias=? AND id!=?")
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
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['templateContent'], $options));
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
        $currentType = $dc->activeRecord->title;
        $this->logger->info('getSubTypes: Current type: ' . $currentType);

        $subTypes = $this->getTemplateOptions('equipment_subTypes');

        // Prüfen, ob für den aktuellen Typ Subtypen definiert wurden
        if (!isset($subTypes[$currentType]) || !is_array($subTypes[$currentType])) {
            // Keine passenden Subtypen gefunden -> leere Liste zurückgeben
            return [];
        }
        // Nur die relevanten Subtypen für diesen Typ zurückgeben
        return $subTypes[$currentType];
    }
}
