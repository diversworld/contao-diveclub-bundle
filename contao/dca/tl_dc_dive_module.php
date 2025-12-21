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
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\Model\DcDiveCourseModel;

$GLOBALS['TL_DCA']['tl_dc_dive_module'] = [
    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_dive_course',
        'ctable' => ['tl_dc_course_exercises'],
        'enableVersioning' => true,
        'markAsCopy' => 'headline',
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ]
        ],
    ],
    // List
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['title', 'shortcode'],
            'flag' => SORT_REGULAR,
            'panelLayout' => 'sort,filter;search,limit'
        ],
        'label' => [
            'fields' => ['title', 'shortcode'],
            'format' => '%s <span style="color:#999">[%s]</span>'
        ],
        'operations' => [
            'edit',
            '!exercises' => [
                'label' => &$GLOBALS['TL_LANG']['tl_dc_courses']['modules'],
                'href' => 'table=tl_dc_course_exercises?filter[modules]=%s',
                'icon' => 'modules.svg',
                'primary' => true,
                'showInHeader' => true
            ],
            'children',
            'copy',
            'cut' => [
                'href' => 'act=paste&amp;mode=create',
                'icon' => 'cut.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"'
            ],
            'delete',
            'toggle',
            'show',
            'new_after' => [
                'label' => ['Neu danach', 'Neue Zuordnung hinzufügen'],
                'href' => 'act=create&amp;mode=1',
                'icon' => 'new.svg', // Das Plus-Icon
                'attributes' => 'onclick="Backend.getScrollOffset()"'
            ],
        ]
    ],
    // Palettes
    'palettes' => [
        '__selector__' => [],
        'default' => '{title_legend},title,alias;
                      {module_legend},shortcode,description,
                      {publish_legend},published,start,stop;',
    ],
    // Fields
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'sorting' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0"
        ),
        'tstamp' => array
        (
            'sql' => "int(10) unsigned NOT NULL default 0"
        ),
        'title' => [
            'label' => ['Modultitel', 'Name des Theorie-Moduls'],
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'filter' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w33'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'alias' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w33'],
            'save_callback' => ['tl_dc_dive_module', 'generateAlias'],
            'sql' => "varchar(255) BINARY NOT NULL default ''",
        ],
        'shortcode' => [
            'label' => ['Kurzcode', 'z. B. M1, T2, etc.'],
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'filter' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 32, 'tl_class' => 'w33 '],
            'sql' => "varchar(32) NOT NULL default ''"
        ],
        'description' => [
            'label' => ['Beschreibung', 'Kurzbeschreibung des Theorie-Moduls'],
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => "text NULL"
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_students']['published'],
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 clr'],
            'sql' => ['type' => 'boolean', 'default' => true],
        ],
        'start' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'clr w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''"
        ],
        'stop' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''"
        ]
    ]
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property DcDiveCourseModel $Courses
 *
 * @internal
 */
class tl_dc_dive_module extends Backend
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
        $aliasExists = function (string $alias) use ($dc): bool {
            return $this->Database->prepare("SELECT id FROM tl_dc_dive_module WHERE alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0;
        };

        // Generate alias if there is none
        if (!$varValue) {
            $varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->headline, DcDiveCourseModel::findByPk($dc->activeRecord->pid)->jumpTo, $aliasExists);
        } elseif (preg_match('/^[1-9]\d*$/', $varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
        } elseif ($aliasExists($varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }
}
