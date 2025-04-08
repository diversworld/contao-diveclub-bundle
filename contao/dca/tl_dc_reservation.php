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
use Diversworld\ContaoDiveclubBundle\DataContainer\DcReservation;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\ReservationTitleCallback;

/**
 * Table tl_dc_reservation
 */
$GLOBALS['TL_DCA']['tl_dc_reservation'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_dc_reservation_items'],
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'tstamp' => 'index',
                'alias' => 'index',
                'published,start,stop' => 'index'
            ]
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTABLE,
            'fields' => ['title', 'alias', 'published'],
            'flag' => DataContainer::SORT_ASC,
            'panelLayout' => 'filter;sort,search,limit'
        ],
        'label' => [
            'fields' => ['title','member_id','asset_type','asset_id','reservation_status','rentalFee'],
            'format' => '%s - %s %s %s - %s %s',
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
            'children',
            'copy',
            'delete',
            'show',
            'toggle'
        ]
    ],
    'palettes'          => [
        '__selector__'      => ['addArticleInfo'],
        'default'           => '{title_legend},title,alias;
                                {details_legend},asset_type,asset_id,member_id;
                                {reservation_legend},reserved_at,picked_up_at,returned_at,reservation_status,rentalFee;
                                {notes_legend},addNotes;
                                {publish_legend},published,start,stop;'
    ],
    'subpalettes'       => [
        'addNotes'          => 'notes',
    ],
    'fields'            => [
        'id'                => [
            'sql'               => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp'            => [
            'sql'               => "int(10) unsigned NOT NULL default 0"
        ],
        'title'             => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation']['title'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'save_callback'     => [[ReservationTitleCallback::class, '__invoke']],
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => false, 'maxlength' => 25, 'tl_class' => 'w33'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'alias'             => [
            'search'            => true,
            'inputType'         => 'text',
            'eval'              => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w33'],
            'save_callback'     => [['tl_dc_reservation', 'generateAlias']],
            'sql'               => "varchar(255) BINARY NOT NULL default ''"
        ],
        'reservation_status'=> [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation']['reservation_status'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options'           => ['reserved', 'borrowed', 'returned', 'cancelled', 'overdue', 'lost', 'damaged', 'missing'],
            'reference'         => &$GLOBALS['TL_LANG']['tl_dc_reservation'],
            'eval'              => ['includeBlankOption' => true, 'submitOnChange' => true, 'chosen'   => true, 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'asset_quantity'    => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation']['asset_quantity'],
            'exclude'           => true,
            'eval'              => ['rgxp' => 'digit', 'mandatory' => false, 'tl_class' => 'w25'],
            'sql'               => "int(10) unsigned NOT NULL default 1",
        ],
        'reserved_at'       => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation']['reserved_at'],
            'inputType'         => 'text',
            'eval'              => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 clr wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'picked_up_at'      => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation']['picked_up_at'],
            'inputType'         => 'text',
            'eval'              => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'returned_at'       => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation']['returned_at'],
            'inputType'         => 'text',
            'eval'              => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'rejected_reason'   => [
            'inputType'         => 'textarea',
            'exclude'           => true,
            'search'            => false,
            'filter'            => true,
            'sorting'           => false,
            'eval'              => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql'               => 'text NULL'
        ],
        'member_id'         => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation']['member_id'],
            'inputType'         => 'select',
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'foreignKey'        => 'tl_member.CONCAT(firstname, " ", lastname)',
            'eval'              => array('submitOnChange' => true, 'includeBlankOption' => true, 'tl_class' => 'w25'),
            'sql'               => "varchar(255) NOT NULL default ''",
            'relation'          => array('type' => 'hasOne', 'load' => 'lazy')
        ],
        'rentalFee'             => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation']['rentalFee'],
            'exclude'           => true,
            'search'            => false,
            'filter'            => true,
            'sorting'           => false,
            'save_callback'     => [['tl_dc_reservation', 'convertPrice']],
            'eval'              => ['rgxp'=>'digit', 'mandatory'=>false, 'tl_class' => 'w25'], // Beachten Sie "rgxp" für Währungsangaben
            'sql'               => "DECIMAL(10,2) NOT NULL default 0.00"
        ],
        'addNotes'          => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation']['addNotes'],
            'exclude'           => true,
            'eval'              => ['submitOnChange' => true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'notes'             => [
            'inputType'         => 'textarea',
            'exclude'           => true,
            'search'            => false,
            'filter'            => true,
            'sorting'           => false,
            'eval'              => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql'               => 'text NULL'
        ],
        'published'         => [
            'toggle'            => true,
            'filter'            => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_DESC,
            'inputType'         => 'checkbox',
            'eval'              => ['doNotCopy' => true, 'tl_class' => 'w50'],
            'sql'               => ['type' => 'boolean', 'default' => false]
        ],
        'start'             => [
            'inputType'         => 'text',
            'eval'              => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 clr wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'stop'              => [
            'inputType'         => 'text',
            'eval'              => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ]
    ]
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @property DcReservation $dcBooking
 *
 * @internal
 */
class tl_dc_reservation extends Backend
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
                ->prepare("SELECT id FROM tl_dc_reservation WHERE alias=? AND id!=?")
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

    public function updateTitleOnMemberIdChange(DataContainer $dc): void
    {
        if (!$dc->activeRecord) {
            return;
        }

        $memberId = (int) $dc->activeRecord->member_id;

        // Falls keine member_id vorhanden ist, nichts tun
        if ($memberId === 0) {
            return;
        }

        // Führende Nullen hinzufügen, um die member_id dreistellig zu machen
        $formattedMemberId = str_pad((string)$memberId, 3, '0', STR_PAD_LEFT);
        // Datum im Format jjjjmmtt
        $currentDate = date('Ymd');

        // Neues Title-Format
        $newTitle = $currentDate . $formattedMemberId;

        $this->db->update(
            'tl_dc_reservation', // Reservierungs-Tabelle
            ['title' => $newTitle],
            ['id' => $dc->id]
        );

        // Message im Backend setzen (optional)
        System::getContainer()->get('session')->getFlashBag()->set(
            'contao.BE.warning',
            sprintf('Title field updated to: %s', $newTitle)
        );
    }
    /**
     * Formatiert den Preis für die Anzeige im Backend
     */
    public function formatPrice($value): string
    {
        return number_format((float)$value, 2, '.', ',') . ' €'; // z. B. "123.45 €"
    }

    /**
     * Konvertiert den eingegebenen Preis zurück ins DB-Format
     */
    public function convertPrice($value): float
    {
        // Logik für leere Eingabe
        if (empty($value)) {
            return 0.00;
        }

        // Entferne eventuell angefügte Währungszeichen und whitespace
        $value = str_replace(['€', ' '], '', $value);

        // Stelle sicher, dass es ein gültiger Dezimalwert ist
        return round((float)$value, 2);
    }
}
