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
use Diversworld\ContaoDiveclubBundle\Model\CheckInvoiceModel;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Table tl_dc_check_article
 */
$GLOBALS['TL_DCA']['tl_dc_check_articles'] = array(
    'config'      => array(
        'dataContainer'     => DC_Table::class,
        'ptable'            => 'tl_calendar_events',
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
    'list'        => array(
        'sorting'           => array(
            'mode'          => DataContainer::MODE_SORTABLE,
            'fields'        => array('title','alias','member','published'),
            'flag'          => DataContainer::SORT_ASC,
            'panelLayout'   => 'filter;sort,search,limit'
        ),
        'label'             => array(
            'fields' => array('title','priceTotal','member','checkId'),
            'format' => '%s - Summe: %s€ %s %s',
        ),
        'global_operations' => array(
            'all' => array(
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            )
        ),
        'operations'        => array(
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
                                {details_legend},member,checkId;
                                {article_legend},invoiceArticles,priceTotal;
                                {notes_legend},notes;
                                {publish_legend},published,start,stop;'
    ),
    'subpalettes'       => array(
    ),
    'fields'            => array(
        'id'                => array(
            'sql'           => "int(10) unsigned NOT NULL auto_increment"
        ),
        'pid'           => [
            'inputType'     => 'text',
            'foreignKey'    => 'tl_dc_tanks.title',
            'eval'          => ['submitOnChange' => true,'mandatory'=>true, 'tl_class' => 'w33 clr'],
            'sql'           => "int(10) unsigned NOT NULL default 0",
        ],
        'tstamp'        => array(
            'sql'           => "int(10) unsigned NOT NULL default '0'"
        ),
        'title'         => array(
            'inputType'     => 'text',
            'label'         => &$GLOBALS['TL_LANG']['tl_dc_check_article']['articleName'],
            'exclude'       => true,
            'search'        => true,
            'filter'        => true,
            'sorting'       => true,
            'flag'          => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'          => array('mandatory' => true, 'maxlength' => 25, 'tl_class' => 'w33'),
            'sql'           => "varchar(255) NOT NULL default ''"
        ),
        'alias'         => array
        (
            'search'        => true,
            'inputType'     => 'text',
            'eval'          => array('rgxp'=>'alias', 'doNotCopy'=>true, 'unique'=>true, 'maxlength'=>255, 'tl_class'=>'w33'),
            'save_callback' => array
            (
                array('tl_dc_check_article', 'generateAlias')
            ),
            'sql'           => "varchar(255) BINARY NOT NULL default ''"
        ),
        'checkId'           => array(
            'inputType'     => 'text',
            'foreignKey'    => 'tl_dc_tanks.pid',
            'eval'          => ['submitOnChange' => true,'mandatory'=>true, 'tl_class' => 'w33 '],
            'sql'           => "int(10) unsigned NOT NULL default 0",
        ),
        'articleName' => [
            'label'     => &$GLOBALS['TL_LANG']['tl_dc_check_article']['articleName'],
            'inputType' => 'text',
            'eval'      => ['groupStyle' => 'width:300px'],
            'sql'       => "varchar(255) NOT NULL default ''"
        ],
        'articleSize' => [
            'label'     => &$GLOBALS['TL_LANG']['tl_dc_check_article']['articleSize'],
            'inputType' => 'select',
            'options'   => ['2','3','5','7','8','10','12','15','18','20','40','80'],
            'eval'      => ['includeBlankOption' => true, 'groupStyle' => 'width:60px'],
            'sql'               => "varchar(20) NOT NULL default ''",
        ],
        'articleNotes'  => [
            'label'     => &$GLOBALS['TL_LANG']['tl_dc_check_article']['articleNotes'],
            'inputType'     => 'textarea',
            'exclude'       => true,
            'search'        => false,
            'filter'        => false,
            'sorting'       => false,
            'eval'          => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
            'sql'           => 'text NULL'
        ],
        'articlePriceNetto' => [
            'label'         => &$GLOBALS['TL_LANG']['tl_dc_check_article']['articlePriceNetto'],
            'inputType'     => 'text',
            'save_callback' => ['tl_dc_check_article', 'calculateBruttoFromNetto'],
            'eval'          => ['groupStyle' => 'width:100px', 'submitOnChange' => true],
            'sql'       => "DECIMAL(10,2) NOT NULL default '0.00'"
        ],
        'articlePriceBrutto' => [
            'label'          => &$GLOBALS['TL_LANG']['tl_dc_check_article']['articlePriceBrutto'],
            'inputType'      => 'text',
            'save_callback'  => array(
                array('tl_dc_check_article', 'calculateBruttoFromNetto')
            ),
            'eval'      => ['groupStyle' => 'width:100px'],
            'sql'       => "DECIMAL(10,2) NOT NULL default '0.00'",
        ],
        'default' => [
            'label'     => &$GLOBALS['TL_LANG']['tl_dc_check_article']['default'],
            'inputType' => 'checkbox',
            'eval'      => ['groupStyle' => 'width:40px'],
            'sql'               => "char(1) NOT NULL default ''"
        ],
        'priceTotal'    => array(
            'inputType'     => 'text',
            'eval'          => array('tl_class'=>'w25 clr'),
            'save_callback' => ['tl_dc_check_article', 'calculateTotalPrice'],
            'sql'           => "DECIMAL(10,2) NOT NULL default '0.00'"
        ),
        'published'     => array(
            'toggle'        => true,
            'filter'        => true,
            'flag'          => DataContainer::SORT_INITIAL_LETTER_DESC,
            'inputType'     => 'checkbox',
            'eval'          => array('doNotCopy'=>true, 'tl_class' => 'w50'),
            'sql'           => array('type' => 'boolean', 'default' => false)
        ),
        'start'         => array(
            'inputType'     => 'text',
            'eval'          => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 clr wizard'),
            'sql'           => "varchar(10) NOT NULL default ''"
        ),
        'stop'          => array(
            'inputType'     => 'text',
            'eval'          => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
            'sql'           => "varchar(10) NOT NULL default ''"
        )
    )
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property Tanks $Tanks
 *
 * @internal
 */
class tl_dc_check_article extends Backend
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
                ->prepare("SELECT id FROM tl_dc_check_article WHERE alias=? AND id!=?")
                ->execute($alias, $dc->id);

            return $result->numRows > 0;
        };

        // Generate the alias if there is none
        if (!$varValue) {
            $varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->title, CheckInvoiceModel::findById($dc->activeRecord->pid)->jumpTo, $aliasExists);
        } elseif (preg_match('/^[1-9]\d*$/', $varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
        } elseif ($aliasExists($varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }
}
