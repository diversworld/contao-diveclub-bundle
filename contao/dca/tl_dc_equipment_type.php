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
    'config'        => [
        'dataContainer'     => DC_Table::class,
        'ctable'            => ['tl_dc_equipment'],
        'enableVersioning'  => true,
        'ondelete_callback' => [],
        'sql'               => [
            'keys' => [
                'id' => 'primary',
                'title' => 'index',
                'alias' => 'index',
                'published,start,stop' => 'index'
            ]
        ],
    ],
    'list'          => [
        'sorting'       => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['title'],
            'flag' => DataContainer::SORT_ASC,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label'         => [
            'fields' => ['title'],
            'showColumns' => false,
            'format' => '%s',
            ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations' => [
            'edit',
            'copy',
            'delete',
            'toggle',
            'show',
        ],
    ],
    'palettes'      => [
        '__selector__'  => ['addNotes'],
        'default'       => '{title_legend},title,alias;
                                {details_legend},type;
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
        'pid'           => [
            'foreignKey'        => 'tl_dc_equipment_type.title',
            'sql'               => "int(10) unsigned NOT NULL default 0",
            'relation'          => ['type' => 'belongsTo', 'load' => 'lazy'], // Typ anpassen, falls notwendig
        ],
        'tstamp'        => [
            'sql'               => "int(10) unsigned NOT NULL default 0"
        ],
        'title'         => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_type']['type'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::MODE_TREE,
            'options_callback'  => ['tl_dc_equipment_type', 'getTypes'],
            'eval'              => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'alias'         => [
            'search'            => true,
            'inputType'         => 'text',
            'eval'              => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'save_callback'     => [
                ['tl_dc_equipment_type', 'generateAlias']
            ],
            'sql'               => "varchar(255) BINARY NOT NULL default ''"
        ],
        'type'          => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_equipment_type']['type'],
            'inputType'         => 'select',
            'options_callback'  => ['tl_dc_equipment_type', 'getTypes'],
            'eval'              => ['mandatory' => true, 'tl_class' => 'w50'],
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

    public function getManufacturers()
    {
        return $this->getTemplateOptions('equipment_manufacturers');
    }

    public function getSizes()
    {
        return $this->getTemplateOptions('equipment_sizes');
    }

    public function getTypes()
    {
        $types = $this->getTemplateOptions('equipment_types');

        // Logge die Rückgabe und prüfe, ob die Werte korrekt geladen werden
        $this->logger = System::getContainer()->get('monolog.logger.contao.general');
        $this->logger->info('getTypes Output: ' . print_r($types, true));

        // Absicherung, dass immer ein Array zurückgegeben wird
        if (!is_array($types)) {
            $types = [];
        }
        //return $this->getTemplateOptions('equipment_types');
        return $types;
    }

    private function getTemplateOptions($templateName)
    {
        $this->logger = System::getContainer()->get('monolog.logger.contao.general');

        // Templatepfad über Contao ermitteln
        $templatePath = TemplateLoader::getPath($templateName, 'html5');

        $this->logger->info('getTemplateOptions: Resolved template path: ' . $templatePath);

        // Überprüfen, ob die Datei existiert
        if (!$templatePath || !file_exists($templatePath)) {
            $this->logger->error('Template file not found: ' . $templatePath);
            return [];
        }

        // Dateiinhalt lesen
        $content = file_get_contents($templatePath);

        $this->logger->info('Template content: ' . $content);

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

        $this->logger->info('Parsed options: ' . print_r($options, true));
        return $options;
        // Rückgabe leer, wenn kein Array generiert werden konnte
    }
}
