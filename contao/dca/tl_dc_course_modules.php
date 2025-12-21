<?php

declare(strict_types=1);

/*
 * DCA: tl_dc_course_modules
 * Module eines Kurses (child table von tl_dc_courses)
 */

use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;

$GLOBALS['TL_DCA']['tl_dc_course_modules'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_dive_course',
        'ctable' => ['tl_dc_course_exercises'],
        'enableVersioning' => true,
        'markAsCopy' => 'headline',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'tstamp' => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['sorting', 'title'],
            'headerFields' => ['title', 'dateStart', 'dateEnd'],
            'flag' => DataContainer::SORT_BOTH,
            'panelLayout' => 'sort;filter,search,limit',
        ],
        'label' => [
            'fields' => ['shortcode', 'title', 'mandatory'],
            'format' => '%s — <span style="color:#86AF35FF; padding-left:8px;">%s — %s</span>',
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
        ],
        'operations' => [
            'edit',
            '!exercises' => [
                'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['exercises'],
                'href' => 'table=tl_dc_course_exercises',
                'icon' => 'jobs.svg',
                'primary' => true,
                'showInHeader' => true
            ],
            'children',
            'copy',
            'cut',
            'delete',
            'toggle',
            'show',
        ],
    ],
    'palettes' => [
        '__selector__' => [],
        'default' => '{title_legend},title,alias;
                      {details_legend},shortcode,mandatory,preModule,description,prerequisites;
                      {publish_legend},published,start,stop'
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'pid' => [
            'foreignKey' => 'tl_dc_dive_course.title',
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'sorting' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['title'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['alias'],
            'inputType' => 'text',
            'save_callback' => ['tl_dc_course_modules', 'generateAlias'],
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) BINARY NOT NULL default ''",
        ],
        'shortcode' => [
            'label' => ['Kurzcode', 'z. B. M1, T2, etc.'],
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'filter' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 32, 'tl_class' => 'w25 clr'],
            'sql' => "varchar(32) NOT NULL default ''"
        ],
        'mandatory' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['mandatory'],
            'inputType' => 'checkbox',
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['tl_class' => 'w25'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'description' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['description'],
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
        'prerequisites' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['description'],
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => "text NULL",
        ],
        'preModule' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['preModule'],
            'inputType' => 'select',
            'options_callback' => ['tl_dc_course_modules', 'getModuleOptions'],
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w25'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_dc_course_modules']['published'],
            'inputType' => 'checkbox',
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['tl_class' => 'w50 clr'],
            'sql' => ['type' => 'boolean', 'default' => false],
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
    ],
];

class tl_dc_course_modules extends Backend
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
                ->prepare("SELECT id FROM tl_dc_course_modules WHERE alias=? AND id!=?")
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

    /**
     * Get all modules grouped by their courses as options
     */
    public function getModuleOptions(DataContainer $dc): array
    {
        $options = [];
        $db = Database::getInstance();

        // Holen aller Kurse und ihrer Module
        // Wir schließen das aktuelle Modul selbst aus ($dc->id), um Zirkelbezüge zu vermeiden
        $objModules = $db->prepare("
            SELECT m.id, m.title AS moduleTitle, c.title AS courseTitle
            FROM tl_dc_course_modules m
            LEFT JOIN tl_dc_dive_course c ON m.pid = c.id
            WHERE m.id != ?
            ORDER BY c.title, m.title
        ")->execute($dc->id ?: 0);

        while ($objModules->next()) {
            $options[$objModules->courseTitle][$objModules->id] = $objModules->moduleTitle;
        }

        return $options;
    }
}
