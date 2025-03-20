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
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['title']               = ["Inventarnummer", "Geben Sie die Inventarnummer ein"];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['alias']               = ["Alias", "Flaschen-Alias"];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['serialNumber']        = ["Seriennummer", "Geben Sie die Seriennummer ein."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['o2clean']             = ["O2 Clean", "Die Flasche ist für Sauerstoff und muss O2 clean sein."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['manufacturer']        = ["Hersteller", "Hersteller des Tauchgerätes."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['bazNumber']           = ["BAZ Nummer", "BAZ Nummer."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['checkId']     	    = ["TÜV Termin", "Termin der letzten TÜV Prüfung."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['owner']               = ["Eigentümer", "Eingentümer des Tauchgerätes."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['lastCheckDate']       = ["letzter TÜV", "Datum der letzten TÜV Prüfung."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['nextCheckDate']       = ["nächster TÜV", "Datum der nächsten TÜV Prüfung."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['pid']                 = ["nächster TÜV-Termin", "Wähle den nächsten TÜV-Termin aus.."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['size']                = ["Größe", "Bitte die Flaschengröße auswählen."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['member']              = ["Eigentümer", "Eigentümer der Flasche."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['addNotes']            = ["Bemerkungen eingeben", "Bemerkungen zum Tauchgerät erfassen."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['published']           = ["Veröffentlichen", "Den Datensatz veröffentlichen."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['start']               = ["Anzeigen ab", "Ab wann soll der Datensatz angezeigt werden."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['stop']                = ["Anzeigen bis", "Bis wann soll der Datensatz angezeigt werden."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['reservation_status']  = ['Status','Status der Reservierung'];

/**
 * References
 */
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['asset_type']              = 'Ressourcentyp';
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['tl_dc_tanks']             = 'Tauchgeräte';
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['tl_dc_regulators']        = 'Atemregler';
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['tl_dc_equipment_types']   = 'Ausrüstung';
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['tl_dc_equipment_subtypes']= 'Ausrüstungsart';

$GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus'] = [
    'avaílable' => 'verfügbar',
    'reserved'  => 'reserviert',
    'borrowed'  => 'ausgeliehen',
    'returned'  => 'zurückgegeben',
    'cancelled' => 'storniert',
    'overdue'   => 'überfällig',
    'lost'      => 'verloren',
    'damaged'   => 'defekt',
    'missing'   => 'vermisst',
];

/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['createInvoiceButton']         = "Rechnung erstellen";
