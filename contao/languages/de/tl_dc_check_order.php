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

$GLOBALS['TL_LANG']['tl_dc_check_order']['member_legend'] = 'Mitglied-Informationen';
$GLOBALS['TL_LANG']['tl_dc_check_order']['tank_legend']   = 'Flaschen-Informationen';
$GLOBALS['TL_LANG']['tl_dc_check_order']['order_legend']  = 'Bestell-Details';
$GLOBALS['TL_LANG']['tl_dc_check_order']['notes_legend']  = 'Anmerkungen';

$GLOBALS['TL_LANG']['tl_dc_check_order']['memberId']         = ['Mitglied', 'Bitte wählen Sie das Mitglied aus.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['firstname']        = ['Vorname', 'Vorname des Kunden (falls kein Mitglied).'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['lastname']         = ['Nachname', 'Nachname des Kunden (falls kein Mitglied).'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['email']            = ['E-Mail', 'E-Mail-Adresse des Kunden.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['phone']            = ['Telefon', 'Telefonnummer des Kunden.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['tankId']           = ['Vereinsflasche / Eigene Flasche', 'Wählen Sie eine bereits registrierte Flasche aus.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['serialNumber']     = ['Seriennummer', 'Seriennummer der Flasche.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['manufacturer']     = ['Hersteller', 'Hersteller der Flasche.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['bazNumber']        = ['BAZ-Nummer', 'BAZ-Nummer der Flasche.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['size']             = ['Flaschengröße', 'Größe der Flasche in Litern.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['o2clean']          = ['O2-clean', 'Ist die Flasche O2-clean?'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['tankData']         = ['Manuelle Flaschendaten', 'Geben Sie Daten für eine neue Flasche ein (Größe, Serie, Hersteller).'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['selectedArticles'] = ['Gewählte Artikel', 'Zusätzliche Leistungen aus dem Angebot.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['totalPrice']       = ['Gesamtpreis', 'Der berechnete Gesamtpreis der Prüfung.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['status']           = ['Status', 'Status der Bestellung.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['notes']            = ['Interne Notizen', 'Zusätzliche Anmerkungen zur Prüfung.'];

$GLOBALS['TL_LANG']['tl_dc_check_order']['sizes'] = [
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

$GLOBALS['TL_LANG']['tl_dc_check_order']['status_reference'] = [
    'ordered'   => 'Bestellt',
    'delivered' => 'Abgegeben',
    'inspection' => 'In Prüfung',
    'checked'   => 'Geprüft',
    'canceled'  => 'Storniert',
    'pickedup'  => 'Abgeholt'
];
