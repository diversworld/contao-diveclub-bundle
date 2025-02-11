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
use \Diversworld\ContaoDiveclubBundle\Model\DcCheckProposalModel;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Table tl_dc_check_invoice
 */
$GLOBALS['TL_DCA']['tl_dc_check_proposal'] = array(
    'config'      => array(
        'dataContainer'     => DC_Table::class,
        'ctable'            => array('tl_dc_check_article'),
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
            'fields'        => array('title','alias','published'),
            'flag'          => DataContainer::SORT_ASC,
            'panelLayout'   => 'filter;sort,search,limit'
        ),
        'label'             => array(
            'fields' => array('title'),
            'format' => '%s %s %s',
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
            'children',
            'copy',
            'delete',
            'show',
            'toggle'
        )
    ),
    'palettes'          => array(
        '__selector__'      => array('addArticleInfo'),
        'default'           => '{title_legend},title,alias;
                                {details_legend},checkId;
                                {vendor_legend},vendorName,vendorStreet,vendorPostal,vendorCity,vendorEmail,vendorPhone,vendorMobile;
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
                array('tl_dc_check_proposal', 'generateAlias')
            ),
            'sql'           => "varchar(255) BINARY NOT NULL default ''"
        ),
        'checkId'           => array(
            'inputType'     => 'text',
            //'foreignKey'    => 'tl_dc_tanks.pid',
            'eval'          => ['submitOnChange' => true,'mandatory'=>false, 'tl_class' => 'w25 '],
            'sql'           => "int(10) unsigned NOT NULL default 0",
        ),
        'vendorName' => [
            'exclude'   => true,
            'flag'      => SORT_STRING,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w33',],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'vendorStreet' => [
            'exclude'   => true,
            'flag'      => SORT_STRING,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w33 clr',],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'vendorPostal' => [
            'exclude'   => true,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'eval'      => ['maxlength' => 12, 'tl_class' => 'w25',],
            'sql'       => "varchar(32) NOT NULL default ''",
        ],
        'vendorCity' => [
            'exclude'   => true,
            'flag'      => SORT_STRING,
            'inputType' => 'text',
            'search'    => true,
            'sorting'   => true,
            'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w33',],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'vendorEmail' => [
            'default'   => null,
            'exclude'   => true,
            'inputType' => 'text',
            'sorting'   => true,
            'eval'      => ['mandatory' => false, 'doNotCopy' => true, 'tl_class' => 'clr w33 wizard',],
            'sql'       => 'int(10) unsigned NULL',
        ],
        'vendorPhone' => [
            'default'   => null,
            'exclude'   => true,
            'inputType' => 'text',
            'sorting'   => true,
            'eval'      => ['mandatory' => false, 'doNotCopy' => true, 'tl_class' => 'w33 wizard',],
            'sql'       => 'int(10) unsigned NULL',
        ],
        'vendorMobile' => [
            'default'   => null,
            'exclude'   => true,
            'inputType' => 'text',
            'sorting'   => true,
            'eval'      => ['mandatory' => false, 'doNotCopy' => true, 'tl_class' => 'w33 wizard',],
            'sql'       => 'int(10) unsigned NULL',
        ],
        'notes'         => array(
            'inputType'     => 'textarea',
            'exclude'       => true,
            'search'        => false,
            'filter'        => true,
            'sorting'       => false,
            'eval'          => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
            'sql'           => 'text NULL'
        ),
        'published'     => array
        (
            'toggle'        => true,
            'filter'        => true,
            'flag'          => DataContainer::SORT_INITIAL_LETTER_DESC,
            'inputType'     => 'checkbox',
            'eval'          => array('doNotCopy'=>true, 'tl_class' => 'w50'),
            'sql'           => array('type' => 'boolean', 'default' => false)
        ),
        'start'         => array
        (
            'inputType'     => 'text',
            'eval'          => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 clr wizard'),
            'sql'           => "varchar(10) NOT NULL default ''"
        ),
        'stop'          => array
        (
            'inputType'     => 'text',
            'eval'          => array('rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'w50 wizard'),
            'sql'           => "varchar(10) NOT NULL default ''"
        )
    )
);

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property DcTanks $Tanks
 *
 * @internal
 */
class tl_dc_check_proposal extends Backend
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
                ->prepare("SELECT id FROM tl_dc_check_proposal WHERE alias=? AND id!=?")
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
        }
        elseif (preg_match('/^[1-9]\d*$/', $varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
        }
        elseif ($aliasExists($varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }
}
