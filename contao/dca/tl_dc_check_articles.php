<?php

declare(strict_types=1);

/*
 * This file is part of DiveClub.
 *
 * (c) Diversworld 2024 <eckhard@diversworld.eu>
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
use Diversworld\ContaoDiveclubBundle\Model\DcCheckInvoiceModel;
use Symfony\Component\String\Slugger\SluggerInterface;
use Contao\CoreBundle\Monolog\ContaoContext;

/**
 * Table tl_dc_check_articles
 */
$GLOBALS['TL_DCA']['tl_dc_check_articles'] = array(
    'config'      => array(
        'dataContainer'     => DC_Table::class,
        'ptable'            => 'tl_dc_check_proposal',
        'enableVersioning'  => true,
        'sql'               => array(
            'keys' => array(
                'id'        => 'primary',
                'tstamp'    => 'index',
                'alias'     => 'index',
                'published,start,stop' => 'index'
            )
        ),
    ),
    'list'              => array(
        'sorting'           => array(
            'mode'          => DataContainer::MODE_SORTABLE,
            'fields'        => array('title','alias','published'),
            'flag'          => DataContainer::SORT_ASC,
            'panelLayout'   => 'filter;sort,search,limit'
        ),
        'label'         => array(
            'fields'        => array('title','articlePriceNetto','articlePriceBrutto'),
            'format'        => '%s - Netto: %s€ Brutto: %s€',
        ),
        'global_operations' => array(
            'all'       => array(
                'href'      => 'act=select',
                'class'     => 'header_edit_all',
                'attributes'=> 'onclick="Backend.getScrollOffset()" accesskey="e"'
            )
        ),
        'operations'    => array(
            'edit',
            'copy',
            'delete',
            'show',
            'toggle'
        )
    ),
    'palettes'          => array(
        '__selector__'      => array('addArticleInfo'),
        'default'           => '{title_legend},title,alias;
                                {article_legend},articleSize,articlePriceNetto,articlePriceBrutto,default;
                                {notes_legend},addNotes;
                                {publish_legend},published,start,stop;'
    ),
    'subpalettes'       => array(
        'addNotes'      => 'notes',
    ),
    'fields'            => array(
        'id'                => [
            'sql'           => "int(10) unsigned NOT NULL auto_increment"
        ],
        'pid'               => [
            'foreignKey'              => 'tl_dc_check_proposal.title',
            'sql'                     => "int(10) unsigned NOT NULL default 0",
            'relation'                => array('type'=>'belongsTo', 'load'=>'lazy')
        ],
        'tstamp'            => [
            'sql'           => "int(10) unsigned NOT NULL default '0'"
        ],
        'title'             => [
            'inputType'     => 'text',
            'label'         => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['articleName'],
            'exclude'       => true,
            'search'        => true,
            'filter'        => true,
            'sorting'       => true,
            'flag'          => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'          => array('mandatory' => true, 'maxlength' => 25, 'tl_class' => 'w33'),
            'sql'           => "varchar(255) NOT NULL default ''"
        ],
        'alias'             => [
            'search'        => true,
            'inputType'     => 'text',
            'eval'          => array('rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w33'),
            'save_callback' => array
            (
                array('tl_dc_check_articles', 'generateAlias')
            ),
            'sql'           => "varchar(255) BINARY NOT NULL default ''"
        ],
        'articleSize'       => [
            'label'     => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['articleSize'],
            'inputType' => 'select',
            'options'   => ['2','3','5','7','8','10','12','15','18','20','40','80'],
            'eval'      => ['includeBlankOption' => true, 'groupStyle' => 'width:60px', 'tl_class'=>'w25'],
            'sql'       => "varchar(20) NOT NULL default ''",
        ],
        'articlePriceNetto' => [
            'label'         => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['articlePriceNetto'],
            'inputType'     => 'text',
            'save_callback'  => array(
                array('tl_dc_check_articles', 'calculatePrices')
            ),
            'eval'          => ['groupStyle' => 'width:100px', 'submitOnChange' => true, 'tl_class'=>'w25'],
            'sql'           => "DECIMAL(10,2) NOT NULL default '0.00'"
        ],
        'articlePriceBrutto'=> [
            'label'          => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['articlePriceBrutto'],
            'inputType'      => 'text',
            'save_callback'  => array(
                array('tl_dc_check_articles', 'calculatePrices')
            ),
            'eval'          => ['groupStyle' => 'width:100px', 'submitOnChange' => true, 'tl_class'=>'w25'],
            'sql'           => "DECIMAL(10,2) NOT NULL default '0.00'",
        ],
        'default'           => [
            'label'         => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['default'],
            'inputType'     => 'checkbox',
            'eval'          => ['groupStyle' => 'width:40px', 'tl_class'=>'w25'],
            'sql'               => "char(1) NOT NULL default ''"
        ],
        'articleNotes'      => [
            'label'         => &$GLOBALS['TL_LANG']['tl_dc_check_articles']['articleNotes'],
            'inputType'     => 'textarea',
            'exclude'       => true,
            'search'        => false,
            'filter'        => false,
            'sorting'       => false,
            'eval'          => array('rte' => 'tinyMCE', 'tl_class'=>'w33'),
            'sql'           => 'text NULL'
        ],
        'published'         => [
            'toggle'        => true,
            'filter'        => true,
            'flag'          => DataContainer::SORT_INITIAL_LETTER_DESC,
            'inputType'     => 'checkbox',
            'eval'          => array('doNotCopy'=>true, 'tl_class' => 'w50'),
            'sql'           => array('type' => 'boolean', 'default' => false)
        ],
        'start'             => [
            'inputType'     => 'text',
            'eval'          => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 clr wizard'),
            'sql'           => "varchar(10) NOT NULL default ''"
        ],
        'stop'              => [
            'inputType'     => 'text',
            'eval'          => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
            'sql'           => "varchar(10) NOT NULL default ''"
        ]
    )
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property Tanks $Tanks
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
        $logger = System::getContainer()->get('monolog.logger.contao');
        $logger->info('Taks 1 generateAlias: ' . $varValue,  ['contao' => new ContaoContext(__METHOD__, ContaoContext::GENERAL)]);
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
            $logger->info('Taks 2 generateAlias: ' . $varValue,  ['contao' => new ContaoContext(__METHOD__, ContaoContext::GENERAL)]);
        } elseif (preg_match('/^[1-9]\d*$/', $varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
        } elseif ($aliasExists($varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }

    public function calculatePrices(mixed $varValue, DataContainer $dc): mixed
    {
        // Wenn das Feld Netto ausgefüllt ist
        if ($dc->field === 'articlePriceNetto') {
            $priceNetto = (float) $varValue; // Netto-Wert auslesen
            $priceBrutto = round($priceNetto * 1.19, 2); // Brutto berechnen

            // Brutto-Preis speichern
            Database::getInstance()
                ->prepare("UPDATE tl_dc_check_articles SET articlePriceBrutto=?, articlePriceNetto=? WHERE id=?")
                ->execute($priceBrutto, $priceNetto, $dc->id);

        } elseif ($dc->field === 'articlePriceBrutto') {
            $priceBrutto = (float) $varValue; // Brutto-Wert auslesen
            $priceNetto = round($priceBrutto / 1.19, 2); // Netto berechnen

            // Netto-Preis speichern
            Database::getInstance()
                ->prepare("UPDATE tl_dc_check_articles SET articlePriceNetto=?, articlePriceBrutto=? WHERE id=?")
                ->execute($priceNetto,$priceBrutto, $dc->id);
        }

        return $varValue; // Immer den Originalwert zurückgeben
    }

}
