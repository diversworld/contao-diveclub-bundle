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
$GLOBALS['TL_LANG']['tl_dc_tanks']['title_legend']      = "Stammdaten";
$GLOBALS['TL_LANG']['tl_dc_tanks']['details_legend']    = "Details zum Tauchgerät";
$GLOBALS['TL_LANG']['tl_dc_tanks']['notes_legend']      = "Notizen";
$GLOBALS['TL_LANG']['tl_dc_tanks']['publish_legend']    = "Veröffentlichen";

/**
* Global operations
*/
$GLOBALS['TL_LANG']['tl_dc_tanks']['new'] = ["Neu", "Ein neues Element anlegen"];

/**
 * Operations
 */
$GLOBALS['TL_LANG']['tl_dc_tanks']['edit']      = "Datensatz mit ID: %s bearbeiten";
$GLOBALS['TL_LANG']['tl_dc_tanks']['copy']      = "Datensatz mit ID: %s kopieren";
$GLOBALS['TL_LANG']['tl_dc_tanks']['delete']    = "Datensatz mit ID: %s löschen";
$GLOBALS['TL_LANG']['tl_dc_tanks']['show']      = "Datensatz mit ID: %s ansehen";
$GLOBALS['TL_LANG']['tl_dc_tanks']['toggle']    = "Datensatz mit ID: %s veröffentlichen";

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_dc_tanks']['title']         = ["Inventarnummer", "Geben Sie die Inventarnummer ein"];
$GLOBALS['TL_LANG']['tl_dc_tanks']['alias']         = ["Alias", "Flaschen-Alias"];
$GLOBALS['TL_LANG']['tl_dc_tanks']['serialNumber']  = ["Seriennummer", "Geben Sie die Seriennummer ein."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['o2clean']       = ["O2 Clean", "Die Flasche ist für Sauerstoff und muss O2 clean sein."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['manufacturer']  = ["Hersteller", "Hersteller des Tauchgerätes."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['bazNumber']     = ["BAZ Nummer", "BAZ Nummer."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['lastCheckDate'] = ["letzter TÜV", "Datum der letzten TÜV Prüfung."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['nextCheckDate'] = ["nächster TÜV", "Datum der nächsten TÜV Prüfung."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['pid']           = ["nächster TÜV-Termin", "Wähle den nächsten TÜV-Termin aus.."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['size']          = ["Größe", "Bitte die Flaschengröße auswählen."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['member']        = ["Eigentümer", "Eigentümer der Flasche."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['addNotes']      = ["Bemerkungen eingeben", "Bemerkungen zum Tauchgerät erfassen."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['published']     = ["Veröffentlichen", "Den Datensatz veröffentlichen."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['start']         = ["Anzeigen ab", "Ab wann soll der Datensatz angezeigt werden."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['stop']          = ["Anzeigen bis", "Bis wann soll der Datensatz angezeigt werden."];
/**
 * References
 */
$GLOBALS['TL_LANG']['tl_dc_tanks']['sizes'] = [
                                                '2' => '2 L',
                                                '3' => '3 L',
                                                '4' => '4 L',
                                                '5' => '5 L',
                                                '7' => '7 L',
                                                '8' => '8 L',
                                                '10' => '10 L',
                                                '12' => '12 L',
                                                '15' => '15 L',
                                                '18' => '18 L',
                                                '20' => '20 L',
                                                '40' => '40 cft',
                                                '80' => '80 cft'
];

/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_dc_tanks']['createInvoiceButton'] = "Rechnung erstellen";
