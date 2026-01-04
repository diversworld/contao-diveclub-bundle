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
$GLOBALS['TL_LANG']['tl_dc_reservation']['title_legend'] = "Stammdaten";
$GLOBALS['TL_LANG']['tl_dc_reservation']['details_legend'] = "Details zum Tauchgerät";
$GLOBALS['TL_LANG']['tl_dc_reservation']['reservation_legend'] = "Reservierung";
$GLOBALS['TL_LANG']['tl_dc_reservation']['publish_legend'] = "Veröffentlichen";

/**
 * Global operations
 */
$GLOBALS['TL_LANG']['tl_dc_reservation']['new'] = ["Neu", "Ein neues Element anlegen"];

/**
 * Operations
 */
$GLOBALS['TL_LANG']['tl_dc_reservation']['edit'] = "Datensatz mit ID: %s bearbeiten";
$GLOBALS['TL_LANG']['tl_dc_reservation']['copy'] = "Datensatz mit ID: %s kopieren";
$GLOBALS['TL_LANG']['tl_dc_reservation']['delete'] = "Datensatz mit ID: %s löschen";
$GLOBALS['TL_LANG']['tl_dc_reservation']['show'] = "Datensatz mit ID: %s ansehen";
$GLOBALS['TL_LANG']['tl_dc_reservation']['toggle'] = "Datensatz mit ID: %s veröffentlichen";

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_dc_reservation']['title'] = ["Reservierung", "Vorgangsnummer der Reservierung"];
$GLOBALS['TL_LANG']['tl_dc_reservation']['alias'] = ['Alias', 'Der Alias ist eine eindeutige Referenz, die anstelle der numerischen ID aufgerufen werden kann.'];
$GLOBALS['TL_LANG']['tl_dc_reservation']['serialNumber'] = ["Seriennummer", "Geben Sie die Seriennummer ein."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['o2clean'] = ["O2 Clean", "Die Flasche ist für Sauerstoff und muss O2 clean sein."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['manufacturer'] = ["Hersteller", "Hersteller des Tauchgerätes."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['bazNumber'] = ["BAZ Nummer", "BAZ Nummer."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['checkId'] = ["TÜV Termin", "Termin der letzten TÜV Prüfung."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['owner'] = ["Eigentümer", "Eingentümer des Tauchgerätes."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['lastCheckDate'] = ["letzter TÜV", "Datum der letzten TÜV Prüfung."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['nextCheckDate'] = ["nächster TÜV", "Datum der nächsten TÜV Prüfung."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['pid'] = ["nächster TÜV-Termin", "Wähle den nächsten TÜV-Termin aus.."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['size'] = ["Größe", "Bitte die Flaschengröße auswählen."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['member'] = ["Eigentümer", "Eigentümer der Flasche."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['rentalFee'] = ["Leihgebühr", "Leihgebühr für die Nutzung des Equipments."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['addNotes'] = ["Bemerkungen eingeben", "Bemerkungen zum Tauchgerät erfassen."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['published'] = ["Veröffentlichen", "Den Datensatz veröffentlichen."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['start'] = ["Anzeigen ab", "Ab wann soll der Datensatz angezeigt werden."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['stop'] = ["Anzeigen bis", "Bis wann soll der Datensatz angezeigt werden."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['reservation_status'] = ['Status', 'Status der Reservierung'];
$GLOBALS['TL_LANG']['tl_dc_reservation']['member_id'] = ['Mitglied', 'Name des Vereinsmitglieds'];
$GLOBALS['TL_LANG']['tl_dc_reservation']['reservedFor'] = ['Reserviert für', 'Name des Mitglied für das die Reservierung erfolgte.'];
$GLOBALS['TL_LANG']['tl_dc_reservation']['reserved_at'] = ['Reserviert am', 'Datum der Reservierung'];
$GLOBALS['TL_LANG']['tl_dc_reservation']['picked_up_at'] = ['Abgeholt am', 'Datum der Abholung'];
$GLOBALS['TL_LANG']['tl_dc_reservation']['returned_at'] = ['Zurückgegeben am', 'Datum der Rückgabe'];

/**
 * References
 */
$GLOBALS['TL_LANG']['tl_dc_reservation']['tl_dc_tanks'] = 'Tauchgeräte';
$GLOBALS['TL_LANG']['tl_dc_reservation']['tl_dc_regulators'] = 'Atemregler';
$GLOBALS['TL_LANG']['tl_dc_reservation']['tl_dc_equipment'] = 'Ausrüstung';

$GLOBALS['TL_LANG']['tl_dc_reservation']['itemStatus'] = [
    'available' => 'verfügbar',
    'reserved' => 'reserviert',
    'borrowed' => 'ausgeliehen',
    'returned' => 'zurückgegeben',
    'cancelled' => 'storniert',
    'overdue' => 'überfällig',
    'lost' => 'verloren',
    'damaged' => 'defekt',
    'missing' => 'vermisst',
];

/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_dc_reservation']['createInvoiceButton'] = "Rechnung erstellen";
