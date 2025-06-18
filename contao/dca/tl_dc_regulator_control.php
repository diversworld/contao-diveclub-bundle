<?php

declare(strict_types=1);

/**
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
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\RegControlHeaderCallback;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\SetRegNextCheckDateCallback;

/**
 * Table tl_dc_check_articles
 */
$GLOBALS['TL_DCA']['tl_dc_regulator_control'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_regulators',
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'tstamp' => 'index',
                'alias' => 'index',
                'published,start,stop' => 'index'
            ]
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['title', 'alias', 'published'],
            'headerFields' => ['title', 'manufacturer', 'regModel1st', 'regModel2ndPri', 'regModel2ndSec'],
            'header_callback' => [RegControlHeaderCallback::class, '__invoke'],
            'flag' => DataContainer::SORT_ASC,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label' => [
            'fields' => ['title', 'midPressurePre', 'inhalePressurePre', 'exhalePressurePre', 'midPressurePost', 'inhalePressurePost', 'exhalePressurePost'],
            'headerFields' => ['title', 'regModel1st', 'regModel2ndPri', 'regModel2ndSec'],
            'format' => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['label_format'], // Dynamische Formatierung
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
            'show',
            'toggle'
        ]
    ],
    'palettes' => [
        '__selector__' => ['addNotes'],
        'default' => '{title_legend},title,alias;
                                {details_legend},actualCheckDate,nextCheckDate,midPressurePre30,midPressurePre200,inhalePressurePre,exhalePressurePre,midPressurePost30,midPressurePost200,inhalePressurePost,exhalePressurePost;
                                {notes_legend},addNotes;
                                {publish_legend},published,start,stop;'
    ],
    'subpalettes' => [
        'addNotes' => 'notes',
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'pid' => [
            'foreignKey' => 'tl_dc_check_proposal.title',
            'sql' => "int(10) unsigned NOT NULL default 0",
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'], // Typ anpassen, falls notwendig
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'title' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['title'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w33'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w33'],
            'save_callback' => [
                ['tl_dc_regulator_control', 'generateAlias']
            ],
            'sql' => "varchar(255) BINARY NOT NULL default ''",
        ],
        'actualCheckDate' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['actualCheckDate'],
            'exclude' => true,
            'sorting' => true,
            'filter' => true,
            'flag' => DataContainer::SORT_YEAR_DESC,
            'eval' => ['submitOnChange' => true, 'rgxp' => 'date', 'doNotCopy' => false, 'datepicker' => true, 'tl_class' => 'w25 wizard'],
            'onsubmit_callback' => [SetRegNextCheckDateCallback::class, '__invoke'],
            'sql' => "bigint(20) NULL"
        ],
        'nextCheckDate' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['nextCheckDate'],
            'exclude' => true,
            'sorting' => true,
            'filter' => true,
            'flag' => DataContainer::SORT_YEAR_DESC,
            'eval' => ['submitOnChange' => true, 'rgxp' => 'date', 'doNotCopy' => false, 'datepicker' => true, 'tl_class' => 'w25 wizard'],
            'sql' => "bigint(20) NULL"
        ],
        'midPressurePre30' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['midPreussurePre30'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25 clr'],
            'sql' => "varchar(50) NOT NULL default ''"
        ],
        'midPressurePre200' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['midPreussurePre200'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25 clr'],
            'sql' => "varchar(50) NOT NULL default ''"
        ],
        'inhalePressurePre' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['inhalePressurePre'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25'],
            'sql' => "varchar(50) NOT NULL default ''"
        ],
        'exhalePressurePre' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['exhalePressurePre'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25'],
            'sql' => "varchar(50) NOT NULL default ''"
        ],
        'midPressurePost30' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['midPreussurePost30'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25 clr'],
            'sql' => "varchar(50) NOT NULL default ''"
        ],
        'midPressurePost200' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['midPreussurePost200'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25 clr'],
            'sql' => "varchar(50) NOT NULL default ''"
        ],
        'inhalePressurePost' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['inhalePressurePost'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval' => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25'],
            'sql' => "varchar(50) NOT NULL default ''"
        ],
        'exhalePressurePost' => [
            'inputType' => 'text',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['exhalePressurePost'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval' => ['mandatory' => true, 'maxlength' => 50, 'tl_class' => 'w25'],
            'sql' => "varchar(50) NOT NULL default ''"
        ],
        'addNotes' => [
            'inputType' => 'checkbox',
            'label' => &$GLOBALS['TL_LANG']['tl_dc_control_card']['addNotes'],
            'exclude' => true,
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'notes' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_regulator_control']['articleNotes'],
            'inputType' => 'textarea',
            'exclude' => true,
            'search' => false,
            'filter' => false,
            'sorting' => false,
            'eval' => ['style' => 'height:60px', 'decodeEntities' => true, 'rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],
        'published' => [
            'toggle' => true,
            'filter' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_DESC,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'start' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 clr wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'stop' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ]
    ]
];

class tl_dc_regulator_control extends Backend
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
}
