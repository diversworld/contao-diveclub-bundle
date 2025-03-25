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
use Diversworld\ContaoDiveclubBundle\DataContainer\DcReservation;
use Diversworld\ContaoDiveclubBundle\EventListener\DataContainer\ItemReservationCallbackListener;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;

/**
 * Table tl_dc_reservation
 */
$GLOBALS['TL_DCA']['tl_dc_reservation_items'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_dc_reservation',
        'enableVersioning' => true,
        'onsubmit_callback' => [ItemReservationCallbackListener::class, '__invoke'],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'tstamp' => 'index',
                'published,start,stop' => 'index'
            ]
        ],
    ],
    'list'                  => [
        'sorting'               => [
            'mode'              => DataContainer::MODE_PARENT,
            'fields'            => ['item_type', 'item_id', 'reservation_status','created_at','updated_at'],
            'headerFields'      => ['title', 'member_id'],
            'flag'              => DataContainer::SORT_ASC,
            'panelLayout'       => 'filter;sort,search,limit'
        ],
        'label'             => [
            'fields'            => ['item_type', 'item_id', 'reservation_status','created_at','updated_at'],
            'showColumns'       => true,
            'format'            => '%s, %s - Status: %s - Erstellt: %s - Geändert: %s',
            'label_callback'    => ['tl_dc_reservation_items', 'setLabel'],
        ],
        'global_operations' => [
            'all'               => [
                'href'          => 'act=select',
                'class'         => 'header_edit_all',
                'attributes'    => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations'        => [
            'edit',
            'copy',
            'delete',
            'show'
        ]
    ],
    'palettes'              => [
        '__selector__'          => ['item_type','addNotes'], // "item_type" als selektierbares Feld definieren
        'default'               => '{title_legend},item_type,item_id;
                                    {details_legend},reservation_status;
                                    {reservation_legend},reserved_at,picked_up_at,returned_at,created_at,updated_at;
                                    {notes_legend},addNotes;
                                    {publish_legend},published,start,stop;',
    ],
    'subpalettes'           => [
        'addNotes'              => 'notes',
        'item_type_tl_dc_equipment_types' => 'types,sub_type', // Subpalette für "tl_dc_equipment_types"
    ],
    'fields'                => [
        'id'                    => [
            'sql'                   => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp'                => [
            'sql'                   => "int(10) unsigned NOT NULL default 0"
        ],
        'pid'               => [
            'foreignKey'        => 'tl_dc_reservation.title',
            'sql'               => "int(10) unsigned NOT NULL default 0",
            'relation'          => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'item_type'         => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['item_type'],
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options'           => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes'],//['tl_dc_regulators', 'tl_dc_tanks', 'tl_dc_equipment_types'],
            'reference'         => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes'],
            'eval'              => ['includeBlankOption' => true, 'submitOnChange' => true, 'chosen' => true, 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'types'             => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['types'], // Sprachvariable
            'exclude'           => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => ['tl_dc_reservation_items', 'getEquipmentTypes'], // Callback-Funktion für dynamische Optionen
            'eval'              => ['mandatory' => false, 'submitOnChange' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''",
        ],
        'sub_type'          => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['sub_type'], // Sprachvariable
            'exclude'           => true,
            'filter'            => true,
            'sorting'           => true,
            'options_callback'  => ['tl_dc_reservation_items', 'getEquipmentSubTypes'], // Callback-Funktion für dynamische Optionen
            'eval'              => ['mandatory' => false, 'submitOnChange' => true,'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''",
        ],
        'reservation_status'=> [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['reservation_status'],
            'default'           => 'reserved',
            'exclude'           => true,
            'search'            => true,
            'filter'            => true,
            'sorting'           => true,
            'options'           => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'],
            'reference'         => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'],
            'eval'              => ['includeBlankOption' => true, 'submitOnChange' => true, 'chosen'   => true, 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
            'sql'               => "varchar(255) NOT NULL default ''"
        ],
        'item_id'           => [
            'inputType'         => 'select',
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['asset_id'],
            'exclude'           => true,
            'filter'            => true,
            'sorting'           => true,
            'explanation'       => 'selected_asset',
            'options_callback'  => ['tl_dc_reservation_items', 'getAvailableAssets'],
            'load_callback'     => ['tl_dc_reservation_items', 'showSelectedAssetHint'],
            'eval'              => [
                'includeBlankOption'    => true,
                'submitOnChange'        => true,
                'chosen'                => true,
                'mandatory'             => true,
                'maxlength'             => 255,
                'helpwizard'            => true,
                'tl_class'              => 'w25'
            ],
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
            'eval'              => ['rgxp' => 'datim', 'submitOnChange' => true, 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'returned_at'       => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['returned_at'],
            'inputType'         => 'text',
            'eval'              => ['rgxp' => 'datim', 'submitOnChange' => true, 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'created_at'        => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['created_at'],
            'inputType'         => 'text',
            'save_callback'     => [
                ['tl_dc_reservation_items', 'setCeratedAt']
            ],
            'eval'              => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w33 wizard'],
            'sql'               => "varchar(10) NOT NULL default ''"
        ],
        'updated_at'        => [
            'label'             => &$GLOBALS['TL_LANG']['tl_dc_reservation_items']['updated_at'],
            'inputType'         => 'text',
            'save_callback'     => [
                ['tl_dc_reservation_items', 'setUpdatedAt']
            ],
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

    public function getAvailableAssets(DataContainer $dc): array
    {
        $helper = new DcaTemplateHelper(); // Instanz der Helper-Klasse
        // Sicherstellen, dass $dc->activeRecord existiert und asset_type gesetzt ist
        if (!$dc->activeRecord || !$dc->activeRecord->item_type) {
            return [];
        }

        // Die ausgewählte Tabelle basierend auf asset_type ermitteln
        $tableName = $dc->activeRecord->item_type;

        // Prüfen, ob die Tabelle existiert (Sicherheitsvorkehrung)
        if (!in_array($tableName, ['tl_dc_tanks', 'tl_dc_regulators', 'tl_dc_equipment_types'], true)) {
            return [];
        }

        // Datenbank-Abfrage zur Ermittlung der verfügbaren Asset-IDs
        $database = Database::getInstance();

        switch ($tableName) {
            case 'tl_dc_tanks':
                $query = sprintf(
                    "SELECT id, title, size, status FROM %s
                   WHERE published = 1
                   ORDER BY title", $tableName
                );

                $result = $database->prepare($query)->execute();
                $options = [];
                while ($result->next()) {
                    $statusText = $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$result->status] ?? $result->status;
                    $status = $result->status ? ' (' . $statusText . ')' : '';

                    $options[$result->id] = $result->title . ' (' . $statusText . ')';
                    $options[$result->id] = $result->size."L - ".$result->title. ' '.$status;
                }
                break;
            case 'tl_dc_regulators':
                $query = sprintf(
                    "SELECT id, title, manufacturer, regModel1st, regModel2ndPri, regModel2ndSec, status FROM tl_dc_regulators
                   WHERE published = 1
                   ORDER BY title"
                );

                $result = $database->prepare($query)->execute();
                $options = [];
                while ($result->next()) {
                    $manufacturerName = $helper->getManufacturers()[$result->manufacturer] ?? 'Unbekannter Hersteller';
                    $regModel1st = $helper->getRegModels1st((int) $result->manufacturer)[$result->regModel1st] ?? 'Unbekanntes Modell';
                    $regModel2ndPri = $helper->getRegModels2nd((int) $result->manufacturer)[$result->regModel2ndPri] ?? 'Unbekanntes Modell';
                    $regModel2ndSec = $helper->getRegModels2nd((int) $result->manufacturer)[$result->regModel2ndSec] ?? 'Unbekanntes Modell';
                    $statusText = $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$result->status] ?? $result->status;
                    $status = $result->status ? ' (' . $statusText . ')' : '';
                    $options[$result->id] = $manufacturerName." - ".$regModel1st.' - '.$regModel2ndPri.' - '.$regModel2ndSec. ' ' . $status;
                }
                break;
            case 'tl_dc_equipment_types':
                $types = (int) $dc->activeRecord->types;
                $subType = (int) $dc->activeRecord->sub_type;
                $query = sprintf("SELECT id, types, subType
                                FROM tl_dc_equipment_types
                                WHERE published = 1 AND types = %s AND subType = %s LIMIT 1", $types, $subType
                );
                $result = $database->prepare($query)->execute();

                $pid = $result->id;

                $query = sprintf(
                    "SELECT id, title, manufacturer, model, size, status FROM  tl_dc_equipment_subtypes
                   WHERE pid = %s AND published = 1
                   ORDER BY title", $pid
                );

                $result = $database->prepare($query)->execute();
                // Optionen für das Dropdown erstellen
                $options = [];
                while ($result->next()) {
                    $manufacturerName = $helper->getManufacturers()[$result->manufacturer] ?? 'Unbekannter Hersteller';
                    $size = $helper->getSizes()[$result->size] ?? 'Unbek. Größe';
                    $statusText = $GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'][$result->status] ?? $result->status;
                    $status = $result->status ? ' (' . $statusText . ')' : '';
                    $options[$result->id] = $manufacturerName.' - '.$result->model.' - '.$size. ' ' . $status;
                }
                break;
        }
        return $options;
    }

    public function showSelectedAssetHint($value, DataContainer $dc)
    {
        if (!$dc->activeRecord->item_id) {
            return $value;
        }

        $database = Database::getInstance();
        $item = $database->prepare("SELECT title, status FROM tl_dc_equipment_subtypes WHERE id = ?")
            ->execute($dc->activeRecord->item_id);

        if ($item->numRows > 0) {
            $status = $item->status === 'reserved' ? ' (Reserviert)' : ' (Verfügbar)';
            $_SESSION['TL_INFO'][] = sprintf(
                'Aktuell ausgewähltes Asset: <strong>%s</strong>%s',
                $item->title,
                $status
            );
        }

        return $value;
    }

    public function getEquipmentTypes(DataContainer $dc): array
    {
        $helper = new DcaTemplateHelper();
        $options = $helper->getEquipmentTypes();

        return $options;
    }

    public function getEquipmentSubTypes(DataContainer $dc): array
    {
        $helper = new DcaTemplateHelper();
        if(!$dc->activeRecord || !$dc->activeRecord->types)
        {
            return[];
        }
        $options = $helper->getSubTypes((int) $dc->activeRecord->types, $dc);

        return $options;
    }
    public function setCeratedAt(string $value, DataContainer $dc): int
    {
        // Prüfen, ob der Wert bereits gesetzt ist
        if (!empty($value)) {
            return (int) $value; // Wenn der Wert existiert, keinen neuen Timestamp setzen
        }

        // Aktuellen Zeitstempel im angegebenen Format zurückgeben
        return time();

    }

    public function setUpdatedAt(string $value, DataContainer $dc): int
    {
        //$datimFormat = $GLOBALS['TL_CONFIG']['datimFormat'] ?? 'Y-m-d H:i:s'; // Fallback, falls nicht gesetzt

        //$actualTimeStamp = date($datimFormat);
        $actualTimeStamp = time();

        // Andernfalls ein neues Datum setzen
        return $actualTimeStamp;
    }

    public function setLabel(array $row, string $label, DataContainer $dc): string
    {
        $args = [$GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes'][$row['item_type']] ?? 'Unbekannt', $row['item_id'] ?? 'Unbekannt', $row['reservation_status'] ?? 'Unbekannt', 'Unbekannt'];

        $database = Database::getInstance();
        $helper = new DcaTemplateHelper(); // Instanz der Helper-Klasse

        // Überprüfen, ob created_at und updated_at gültige Werte enthalten
        $created = !empty($row['created_at']) && date($row['created_at']) !== false
            ? date($GLOBALS['TL_CONFIG']['datimFormat'], (int) $row['created_at']) // Fallback, falls nicht gesetzt))
            : 'Unbekannt';

        $updated = !empty($row['updated_at']) && date($row['updated_at']) !== false
            ? date($GLOBALS['TL_CONFIG']['datimFormat'], (int) $row['updated_at'])
            : 'Unbekannt';

        $args[3] = $created;
        $args[4] = $updated;

        // Prüfen, ob "item_type" und "item_id" gesetzt sind
        if (!$row['item_type'] || !$row['item_id']) {
            $args[1] = 'Unbekannt'; // Ersatzwert, falls Daten nicht gesetzt sind
            return $args;
        }

        // Tabelle bestimmen basierend auf item_type
        switch ($row['item_type']) {
            case 'tl_dc_tanks':
                $result = $database
                    ->prepare("SELECT title, size FROM tl_dc_tanks WHERE id = ?")
                    ->execute($row['item_id']);
                // Fall: Kein Ergebnis gefunden
                if (!$result->numRows) {
                    $args[1] = 'Nicht gefunden';
                } else {
                    // Bezeichnung zusammenbauen
                    $args[1] = 'Größe: ' . $result->size . 'L - Inverntarnummer: ' . $result->title;
                }
                break;

            case 'tl_dc_regulators':
                $result = $database
                    ->prepare("SELECT title, manufacturer, regModel1st, regModel2ndPri, regModel2ndSec FROM tl_dc_regulators WHERE id = ?")
                    ->execute($row['item_id']);
                if (!$result->numRows) {
                    $args[1] = 'Nicht gefunden';
                } else {
                    $manufacturerName = $helper->getManufacturers()[$result->manufacturer] ?? 'Unbekannter Hersteller';
                    $regModel1st = $helper->getRegModels1st((int) $result->manufacturer, $dc)[$result->regModel1st] ?? 'Unbek. 1. Stufe';
                    $regModel2ndPri = $helper->getRegModels2nd((int) $result->manufacturer, $dc)[$result->regModel2ndPri] ?? 'Unbek. 2. Stufe (Primär)';
                    $regModel2ndSec = $helper->getRegModels2nd((int) $result->manufacturer, $dc)[$result->regModel2ndSec] ?? 'Unbek. 2. Stufe (Sekundär)';

                    // Ausgabe formatieren
                    $args[1] = implode(', ', [
                        'Hersteller: ' . $manufacturerName,
                        'Inventarnummer: ' . $result->title,
                        '1. Stufe: ' . $regModel1st,
                        '2. Stufe (Primär): ' . $regModel2ndPri,
                        '2. Stufe (Sekundär): ' . $regModel2ndSec
                    ]);
                }
                break;

            case 'tl_dc_equipment_types':
                $result = $database
                    ->prepare("SELECT title, manufacturer, model, size FROM tl_dc_equipment_subtypes WHERE id = ?")
                    ->execute($row['item_id']);
                if (!$result->numRows) {
                    $args[1] = 'Nicht gefunden';
                } else {
                    $manufacturerName = $helper->getManufacturers()[$result->manufacturer] ?? 'Unbekannter Hersteller';
                    //$model = $helper->getSubTypes();
                    $size = $helper->getSizes();
                    $args[1] = $manufacturerName . ', ' . $result->model . ' (' . $size[$result->size] . ')';
                }
                break;

            default:
                $args[1] = 'Unbekannter Typ';
                break;
        }

        $args[3] = $created;
        $args[4] = $updated;

        return vsprintf('%s, %s - Status: %s - Erstellt: %s - Geändert: %s', $args);

    }

}
