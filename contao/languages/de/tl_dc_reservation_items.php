<?php

declare(strict_types=1);

/*
 * This file is part of diveclub.
 *
 * (c) Diversworld 2024 <eckhard@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['title_legend']        = "Stammdaten";
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['details_legend']      = "Details zum Tauchgerät";
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['reservation_legend']  = "Reservierung";
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['notes_legend']        = "Bemerkungen";
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['publish_legend']      = "Veröffentlichen";

/**
* Global operations
*/
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['new']         = ["Neu", "Ein neues Element anlegen"];

/**
 * Operations
 */
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['edit']        = "Datensatz mit ID: %s bearbeiten";
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['copy']        = "Datensatz mit ID: %s kopieren";
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['delete']      = "Datensatz mit ID: %s löschen";
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['show']        = "Datensatz mit ID: %s ansehen";
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['toggle']      = "Datensatz mit ID: %s veröffentlichen";

/**
 * Fields
 */

$GLOBALS['TL_LANG']['tl_dc_reservation_items']['manufacturer']          = ["Hersteller", "Hersteller des Tauchgerätes."];

$GLOBALS['TL_LANG']['tl_dc_reservation_items']['pid']                   = ["nächster TÜV-Termin", "Wähle den nächsten TÜV-Termin aus.."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['size']                  = ["Größe", "Bitte die Flaschengröße auswählen."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['member']                = ["Eigentümer", "Eigentümer der Flasche."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['reservation_status']    = ['Status','Status der Reservierung'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['item_type']             = ['Kategorie', 'Bitte wähle den Ausrüstungstyp aus.'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['item_id']               = ['Gewähltes Teil', 'Bitte wähle die Art der Ausrüstungs aus.'];


$GLOBALS['TL_LANG']['tl_dc_reservation_items']['types']                 = ['Ausrüstung', 'Bitte wähle die Art der Ausrüstungs aus.'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['sub_type']              = ['Ausrüstungsteil', 'Bitte wähle die Art der Ausrüstungs aus.'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['reserved_at']           = ['Reserviert', 'Bitte geben Sie das Reservierungsdatum an.'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['created_at']            = ['Erstellt', 'Das Datum der Erstellung der Reservierung wird automatisch erstellt.'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['updated_at']            = ['Aktualisiert', 'Das Datum der Aktualisierung der Reservierung wird automatisch erstellt.'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['picked_up_at']          = ['Abgeholt', 'Bitte geben Sie das Datum der Abholung an.'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['returned_at']           = ['Zurückgegeben', 'Bitte geben Sie das Datu der Rückgabe an.'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['addNotes']              = ["Bemerkungen eingeben", "Bemerkungen zum Tauchgerät erfassen."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['published']             = ["Veröffentlichen", "Den Datensatz veröffentlichen."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['start']                 = ["Anzeigen ab", "Ab wann soll der Datensatz angezeigt werden."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['stop']                  = ["Anzeigen bis", "Bis wann soll der Datensatz angezeigt werden."];

/**
 * Explanations
 */
$GLOBALS['TL_LANG']['XPL']['selected_asset'] = [
    [
        'Hinweis',
        'Der Status des momentan ausgewählte Assets wird auf den <strong>hier gewählten Status </strong> geändert, wenn sie den Status ändern.'
    ]
];
/**
 * References
 */
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes']             = [
        'tl_dc_tanks'       => 'Tauchgeräte',
        'tl_dc_regulators'  => 'Atemregler',
        'tl_dc_equipment'   => 'Ausrüstung',
    ];

$GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'] = [
        'available' => 'verfügbar',
        'reserved'  => 'reserviert',
        'borrowed'  => 'ausgeliehen',
        'returned'  => 'zurückgegeben',
        'cancelled'  => 'storniert',
        'overdue'   => 'überfällig',
        'lost'      => 'verloren',
        'damaged'   => 'defekt',
        'missing'   => 'vermisst',
    ];

/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['createInvoiceButton']         = "Rechnung erstellen";
