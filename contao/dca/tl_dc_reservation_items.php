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

/**
 * Table tl_dc_reservation
 */
$GLOBALS['TL_DCA']['tl_dc_reservation_items'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_reservation',
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
            'fields' => ['title', 'member_id', 'asset_type', 'asset_id'],
            'format' => '%s - %s %s %s',
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
    'palettes'          => [
        '__selector__'      => ['addArticleInfo'],
        'default'           => '{title_legend},title,alias;
                                {details_legend},item_type,item_id,reservation_status;
                                {reservation_legend},reserved_at,picked_up_at,returned_at,created_at,updated_at;
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
        'pid'               => [
            'foreignKey'        => 'tl_dc_reservation.title',
            'sql'               => "int(10) unsigned NOT NULL default 0",
            'relation'          => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],/*
        'title'             => [
            'inputType'         => 'text',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['title'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'flag'              => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'              => ['mandatory' => true, 'maxlength' => 25, 'tl_class' => 'w33'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'alias'             => [
            'search'            => true,
            'inputType'         => 'text',
            'eval'              => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w33'],
            'save_callback'     => [['tl_dc_reservation', 'generateAlias']],
            'sql'               => "varchar(255) BINARY NOT NULL default ''"
        ],*/
        'item_type'        => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['asset_type'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options'           => ['tl_dc_regulators', 'tl_dc_tanks', 'tl_dc_equipment_types'],
            'reference'         => &$GLOBALS['TL_LANG']['tl_dc_reservation_items'],
            'eval'              => ['includeBlankOption' => true, 'submitOnChange' => true, 'chosen'   => true, 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'reservation_status'        => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['reservation_status'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options'           => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'],
            'reference'         => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'],
            'eval'              => ['includeBlankOption' => true, 'submitOnChange' => true, 'chosen'   => true, 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'item_id'          => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['asset_id'],
            'exclude'           => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => ['tl_dc_reservation_items', 'getAvailableAssets'],
            'eval'              => ['includeBlankOption' => true, 'submitOnChange' => true, 'chosen'   => true, 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
            'sql'               => "int(10) unsigned NOT NULL default 0",
        ],
        'reserved_at'       => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['reserved_at'],
            'inputType'         => 'text',
            'eval'              => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 clr wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'picked_up_at'      => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['picked_up_at'],
            'inputType'         => 'text',
            'eval'              => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'returned_at'       => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['returned_at'],
            'inputType'         => 'text',
            'eval'              => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'created_at'       => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['created_at'],
            'inputType'         => 'text',
            'eval'              => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'updated_at'       => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['updated_at'],
            'inputType'         => 'text',
            'eval'              => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'addNotes'          => [
            'inputType'         => 'checkbox',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['addNotes'],
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
class tl_dc_reservation_items extends Backend
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
                ->prepare("SELECT id FROM tl_dc_reservation_items WHERE alias=? AND id!=?")
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

    public function getAvailableAssets(DataContainer $dc): array
    {
        dump($dc->activeRecord);
        // Sicherstellen, dass $dc->activeRecord existiert und asset_type gesetzt ist
        if (!$dc->activeRecord->item_type) {
            return [];
        }

        // Die ausgewählte Tabelle basierend auf asset_type ermitteln
        $tableName = $dc->activeRecord->item_type;
        dump($tableName);
        // Prüfen, ob die Tabelle existiert (Sicherheitsvorkehrung)
        if (!in_array($tableName, ['tl_dc_tanks', 'tl_dc_regulators', 'tl_dc_equipment_types'], true)) {
            return [];
        }

        // Datenbank-Abfrage zur Ermittlung der verfügbaren Asset-IDs
        $database = Database::getInstance();

        switch ($tableName) {
            case 'tl_dc_tanks':
                $query = sprintf(
                    "SELECT id, title, size FROM %s
                   WHERE published = 1 AND status = 'available'
                   ORDER BY title", $tableName
                );
                dump($query);
                $result = $database->prepare($query)->execute();
                break;
            case 'tl_dc_regulators':
                $query = sprintf(
                    "SELECT id, title, manufacturer, regModel1st, regModel2ndPri, regModel2ndSec FROM %s
                   WHERE published = 1 AND status = 'available'
                   ORDER BY title", $tableName
                );
                dump($query);
                $result = $database->prepare($query)->execute();
                break;
            case 'tl_dc_equipment_types':
                $tableName = 'tl_dc_equipment_subtypes';
                $query = sprintf(
                    "SELECT id, title, manufacturer, model, size FROM %s
                   WHERE published = 1 AND status = 'available'
                   ORDER BY title", $tableName
                );
                dump($query);
                $result = $database->prepare($query)->execute();
                break;
        }

        dump($result);
        // Optionen für das Dropdown erstellen
        $options = [];
        while ($result->next()) {
            $options[$result->id] = $result->size."L - ".$result->title;
        }
        dump($options);
        return $options;
    }

}
