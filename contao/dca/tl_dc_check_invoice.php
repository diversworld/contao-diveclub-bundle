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
use Diversworld\ContaoDiveclubBundle\Model\DcCheckInvoiceModel;

/**
 * Table tl_dc_check_invoice
 */
$GLOBALS['TL_DCA']['tl_dc_check_invoice'] = array(
    'config'      => array(
        'dataContainer'     => DC_Table::class,
        'ptable'            => 'tl_dc_tanks',
        'enableVersioning'  => true,
        'sql'               => array(
            'keys' => array(
                'id'        => 'primary',
                'pid'       => 'index',
                'tstamp'    => 'index',
                'alias'     => 'index',
                'published,start,stop' => 'index'
            )
        ),
    ),
    'list'        => array(
        'sorting'           => array(
            'mode'          => DataContainer::MODE_PARENT,
            'fields'        => array('title','alias','member','published'),
            'headerFields'  => ['title', 'manufacturer', 'size', 'lastCheckDate', 'nextCheckDate'],
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
            'children',
            'copy',
            'delete',
            'show',
            'toggle'
        )
    ),
    'palettes'          => array(
        '__selector__'      => array('addNotes'),
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
            'foreignKey'    => 'tl_dc_tanks.title',
            'sql'               => "int(10) unsigned NOT NULL default 0",
            'relation'          => ['type' => 'belongsTo', 'load' => 'lazy'], // Typ anpassen, falls notwendig
        ],
        'tstamp'        => array(
            'sql'           => "int(10) unsigned NOT NULL default 0"
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
                array('tl_dc_check_invoice', 'generateAlias')
            ),
            'sql'           => "varchar(255) BINARY NOT NULL default ''"
        ),
        'member'            => array(
            'inputType'         => 'select',
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'eval'              => array('includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w33'),
            'options_callback'  => array('tl_dc_check_invoice', 'getMemberOptions'),
            'sql'               => "varchar(255) NOT NULL default ''",
        ),
        'checkId'           => array(
            'inputType'     => 'text',
            'foreignKey'    => 'tl_dc_tanks.pid',
            'eval'          => ['submitOnChange' => true,'mandatory'=>true, 'tl_class' => 'w33 '],
            'sql'           => "int(10) unsigned NOT NULL default 0",
        ),
        'invoiceArticles'  => array(
            'inputType' => 'multiColumnEditor',
            'tl_class'  => 'compact',
            'eval'      => [
                'submitCallback' => ['tl_dc_check_invoice', 'calculateTotalPrice'],
                'multiColumnEditor' => [
                    'skipCopyValuesOnAdd' => true,
                    'editorTemplate' => 'multi_column_editor_backend_default',
                    'fields' => [
                        'articleName' => [
                            'label' => &$GLOBALS['TL_LANG']['tl_dc_check_invoice']['articleName'],
                            'inputType' => 'text',
                            'eval' => ['groupStyle' => 'width:300px']
                        ],
                        'articleSize' => [
                            'label'     => &$GLOBALS['TL_LANG']['tl_dc_check_invoice']['articleSize'],
                            'inputType' => 'select',
                            'options'   => ['2','3','5','7','8','10','12','15','18','20','40','80'],
                            'eval'      => ['includeBlankOption' => true, 'groupStyle' => 'width:60px']
                        ],
                        'articleNotes'  => [
                            'label'     => &$GLOBALS['TL_LANG']['tl_dc_check_invoice']['articleNotes'],
                            'inputType' => 'textarea',
                            'eval'      => ['groupStyle' => 'width:400px']
                        ],
                        'articlePriceNetto' => [
                            'label'     => &$GLOBALS['TL_LANG']['tl_dc_check_invoice']['articlePriceNetto'],
                            'inputType' => 'text',
                            'save_callback' => ['tl_dc_check_invoice', 'calculateBruttoFromNetto'],
                            'eval'      => ['groupStyle' => 'width:100px', 'submitOnChange' => true],
                        ],
                        'articlePriceBrutto' => [
                            'label'     => &$GLOBALS['TL_LANG']['tl_dc_check_invoice']['articlePriceBrutto'],
                            'inputType' => 'text',
                            'eval'      => ['groupStyle' => 'width:100px']
                        ],
                        'default' => [
                            'label'     => &$GLOBALS['TL_LANG']['tl_dc_check_invoice']['default'],
                            'inputType' => 'checkbox',
                            'eval'      => ['groupStyle' => 'width:40px']
                        ],
                    ]
                ]
            ],
            'save_callback' => array(
                array('tl_dc_check_invoice', 'calculateBruttoFromNetto')
            ),
            'sql'       => "blob NULL"
        ),
        'priceTotal'         => array
        (
            'inputType'     => 'text',
            'eval'          => array('tl_class'=>'w25 clr'),
            'save_callback' => ['tl_dc_check_invoice', 'calculateTotalPrice'],
            'sql'           => "DECIMAL(10,2) NOT NULL default '0.00'"
        ),
        'addNotes'          => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation']['addNotes'],
            'exclude'           => true,
            'eval'              => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
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
 * @internal
 */
class tl_dc_check_invoice extends Backend
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
                ->prepare("SELECT id FROM tl_dc_check_invoice WHERE alias=? AND id!=?")
                ->execute($alias, $dc->id);

            return $result->numRows > 0;
        };

        // Generate the alias if there is none
        if (!$varValue)
        {
            $varValue = System::getContainer()->get('contao.slug')->generate($dc->activeRecord->title, DcCheckInvoiceModel::findById($dc->activeRecord->pid)->jumpTo, $aliasExists);
        }
        elseif (preg_match('/^[1-9]\d*$/', $varValue))
        {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
        }
        elseif ($aliasExists($varValue))
        {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }

    public function getMemberOptions(): array
    {
        $members = Database::getInstance()->execute("SELECT id, CONCAT(firstname, ' ', lastname) as name FROM tl_member")->fetchAllAssoc();
        $options = array();

        foreach($members as $member)
        {
            $options[$member['id']] = $member['name'];
        }

        return $options;
    }

    public function calculateBruttoFromNetto($value, DataContainer $dc)
    {
        $invoiceArticles = unserialize($value);

        foreach ($invoiceArticles as &$item) {
            // Konvertieren Sie den String-Wert zu einem numerischen Wert
            $nettoPrice = (float) str_replace(',', '.', $item['articlePriceNetto']);

            // Berechnen Sie den Brutto-Wert basierend auf dem MwSt.-Satz (angenommen 19%)
            $grossPrice = $nettoPrice * 1.19;
            $grossRoundedPrice = ceil($grossPrice / 0.05) * 0.05;

            // Aktualisieren Sie den Brutto-Wert im aktuellen Artikel
            $item['articlePriceBrutto'] = number_format($grossRoundedPrice, 2);

            $totalPrice += $grossRoundedPrice;
        }

        // Aktualisieren Sie den Gesamtpreis in der Datenbank
        Database::getInstance()->prepare("UPDATE tl_dc_check_invoice SET priceTotal = ? WHERE id = ?")
            ->execute(number_format($totalPrice, 2), $dc->id);

        // Geben Sie das aktualisierte invoiceArticles-Array zurück, um es in der Datenbank zu speichern
        return serialize($invoiceArticles);
    }
}
