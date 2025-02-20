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
use Diversworld\ContaoDiveclubBundle\DataContainer\Tanks;
use Symfony\Component\String\Slugger\SluggerInterface;
use Contao\CoreBundle\Monolog\ContaoContext;
use Diversworld\ContaoDiveclubBundle\DataContainer\DcCheckProposal;

/**
 * Table tl_dc_check_articles
 */
$GLOBALS['TL_DCA']['tl_dc_check_articles'] = [
    'config'        => [
        'dataContainer'     => DC_Table::class,
        'ptable'            => 'tl_dc_check_proposal',
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
            'mode'          => DataContainer::MODE_PARENT,
            'fields'        => ['title','alias','published'],
            'headerFields'  => ['title', 'vendorName', 'proposalDate'],
            'flag'          => DataContainer::SORT_ASC,
            'panelLayout'   => 'filter;sort,search,limit',
        ],
        'label'             => [
            'fields'        => ['title','articlePriceNetto','articlePriceBrutto'],
            'format'        => '%s - Netto: %s€ Brutto: %s€',
        ],
        'global_operations' => [
            'all'       => [
                'href'      => 'act=select',
                'class'     => 'header_edit_all',
                'attributes'=> 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations'        => [
            'edit',
            'copy',
            'delete',
            'show',
            'toggle'
        ]
    ],
    'palettes'      => [
        '__selector__'      => ['addArticleInfo'],
        'default'           => '{title_legend},title,alias;
                                {article_legend},articleSize,articlePriceNetto,articlePriceBrutto,default;
                                {notes_legend},addNotes;
                                {publish_legend},published,start,stop;'
    ],
    'subpalettes'   => [
        'addNotes'          => 'notes',
    ],
    'fields'        => [
        'id'                => [
            'sql'           => "int(10) unsigned NOT NULL auto_increment"
        ],
        'pid'               => [
            'foreignKey'        => 'tl_dc_check_proposal.title',
            'sql'               => "int(10) unsigned NOT NULL default 0",
            'relation'          => ['type' => 'belongsTo', 'load' => 'lazy'], // Typ anpassen, falls notwendig
        ],
        'tstamp'            => [
            'sql'               => "int(10) unsigned NOT NULL default 0"
        ],
        'title'             => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['articleName'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w33'],
            'sql'               => "varchar(255) NOT NULL default ''",
        ],
        'alias'             => [
            'search'        => true,
            'inputType'     => 'text',
            'eval'          => ['rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w33'],
            'save_callback' => [
                ['tl_dc_check_articles', 'generateAlias']
            ],
            'sql'           => "varchar(255) BINARY NOT NULL default ''",
        ],
        'articleSize'       => [
            'label'         => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['articleSize'],
            'inputType'     => 'select',
            'options'       => ['2','3','5','7','8','10','12','15','18','20','40','80'],
            'eval'          => ['includeBlankOption' => true, 'groupStyle' => 'width:60px', 'tl_class'=>'w25'],
            'sql'           => "varchar(20) NOT NULL default ''",
        ],
        'articlePriceNetto' => [
            'label'         => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['articlePriceNetto'],
            'inputType'     => 'text',
            'save_callback' => [
                ['tl_dc_check_articles', 'calculatePrices']
            ],
            'eval'          => ['submitOnChange' => true, 'tl_class'=>'w25'],
            'sql'           => "DECIMAL(10,2) NOT NULL default '0.00'",
        ],
        'articlePriceBrutto'=> [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['articlePriceBrutto'],
            'inputType'         => 'text',
            'save_callback'     => [
                ['tl_dc_check_articles', 'calculatePrices']
            ],
            'eval'          => ['submitOnChange' => true, 'tl_class'=>'w25'],
            'sql'           => "DECIMAL(10,2) NOT NULL default '0.00'",
        ],
        'default'           => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['default'],
            'inputType'         => 'checkbox',
            'eval'              => ['tl_class'=>'w25'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'articleNotes'      => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['articleNotes'],
            'inputType'         => 'textarea',
            'exclude'           => true,
            'search'            => false,
            'filter'            => false,
            'sorting'           => false,
            'eval'              => ['rte' => 'tinyMCE', 'tl_class'=>'w33'],
            'sql'               => 'text NULL',
        ],
        'published'         => [
            'toggle'            => true,
            'filter'            => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_DESC,
            'inputType'         => 'checkbox',
            'eval'              => ['doNotCopy'=>true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false],
        ],
        'start'             => [
            'inputType'         => 'text',
            'eval'              => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 clr wizard'],
            'sql'               => "varchar(10) NOT NULL default ''",
        ],
        'stop'              => [
            'inputType'         => 'text',
            'eval'              => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''",
        ]
    ]
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property DcTanks $DcTanks
 *
 * @internal
 */
class tl_dc_check_articles extends Backend
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
                ->prepare("SELECT id FROM tl_dc_check_articles WHERE alias=? AND id!=?")
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

    public function calculatePrices(mixed $varValue, DataContainer $dc): mixed
    {
        // Fall: Netto-Wert wurde eingegeben
        if ($dc->field === 'articlePriceNetto') {
            $priceNetto = (float) $varValue; // Netto-Wert speichern
            $priceBrutto = round($priceNetto * 1.19, 2); // Brutto berechnen

            // Synchronisierung über activeRecord
            $dc->activeRecord->articlePriceBrutto = $priceBrutto;

            // Preise speichern
            Database::getInstance()
                ->prepare("UPDATE tl_dc_check_articles SET articlePriceBrutto=? WHERE id=?")
                ->execute($priceBrutto, $dc->id);

        } elseif ($dc->field === 'articlePriceBrutto') {
            // Fall: Brutto-Wert wurde eingegeben
            $priceBrutto = (float) $varValue; // Brutto-Wert speichern
            $priceNetto = round($priceBrutto / 1.19, 2); // Netto berechnen

            // Synchronisierung über activeRecord
            $dc->activeRecord->articlePriceNetto = $priceNetto;

            // Preise speichern
            Database::getInstance()
                ->prepare("UPDATE tl_dc_check_articles SET articlePriceNetto=? WHERE id=?")
                ->execute($priceNetto, $dc->id);
        }

        // Rückgabe des aktuellen Feldes: Immer den eingegebenen Wert zurückgeben
        return $varValue;
    }
}
