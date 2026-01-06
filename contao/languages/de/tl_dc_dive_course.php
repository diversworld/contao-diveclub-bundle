<?php

declare(strict_types=1);

/*
 * This file is part of ContaoDiveclubBundle.
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
$GLOBALS['TL_LANG']['tl_dc_dive_course']['first_legend'] = "Basis Einstellungen";
$GLOBALS['TL_LANG']['tl_dc_dive_course']['details_section'] = "Kursdetails";
$GLOBALS['TL_LANG']['tl_dc_dive_course']['drequirenment_section'] = "Anforderungen";
$GLOBALS['TL_LANG']['tl_dc_dive_course']['image_legend'] = "Bild Einstellungen";
$GLOBALS['TL_LANG']['tl_dc_dive_course']['publish_legend'] = "Veröffentlichen";
/**
 * Global operations
 */
$GLOBALS['TL_LANG']['tl_dc_dive_course']['new'] = ["Neu", "Ein neues Element anlegen"];

/**
 * Operations
 */
$GLOBALS['TL_LANG']['tl_dc_dive_course']['edit'] = "Datensatz mit ID: %s bearbeiten";
$GLOBALS['TL_LANG']['tl_dc_dive_course']['copy'] = "Datensatz mit ID: %s kopieren";
$GLOBALS['TL_LANG']['tl_dc_dive_course']['delete'] = "Datensatz mit ID: %s löschen";
$GLOBALS['TL_LANG']['tl_dc_dive_course']['show'] = "Datensatz mit ID: %s ansehen";
$GLOBALS['TL_LANG']['tl_dc_dive_course']['modules'] = ['Modulverwaltung', 'Verwalten der Kursmodule'];

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_dc_dive_course']['title'] = ['Kurstitel', 'Titel des Tauchkurses'];
$GLOBALS['TL_LANG']['tl_dc_course_modules']['alias'] = ['Alias', 'Der Alias ist eine eindeutige Referenz, die anstelle der numerischen ID aufgerufen werden kann.'];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['instructor'] = ['Tauchlehrer', 'Verantwortlicher Ausbilder'];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['course_type'] = ['Kurstyp', 'Art des Kurses (z. B. OWD, AOWD, Rescue)'];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['max_participants'] = ["Max. Teilnehmer", "Maximale Ánzahl der Teilnehmer"];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['price'] = ['Preis', 'Teilnahmegebühr'];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['dateStart'] = ["Kursbeginn", "Datum an dem der Kurs beginnt."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['dateEnd'] = ["Kursende", "Datum an dem der Kurs endet."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['category'] = ["Kategorie", "Wählen Sie die Kurskategorie aus."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['description'] = ["Beschreibung", "Bitte geben Sie die Kursbeschreibung an."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['requirements'] = ["Voraussetzungen", "Geben Sie die Voraussetzngen für die Kursteilnahme ein."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['addImage'] = ["Bild hinzufügen", "Hier können Sie die Bild Einstellungen aktivieren."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['fullsize'] = ["Textarea", "Geben Sie einen Text ein"];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['size'] = ["Bildgröße", "Geben Sie die Bildgröße an. "];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['floating'] = ["Anordnung", "Wo soll das Bild angezeigt werden. "];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['overwriteMeta'] = ["Metadaten überschreiben", "Überschreiben Sie die Metadaten des Bildes."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['published'] = ['Veröffentlicht', 'Markieren Sie das Equipment als veröffentlicht.'];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['start'] = ['Startdatum', 'Geben Sie ein Startdatum an.'];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['stop'] = ['Enddatum', 'Geben Sie ein Enddatum an.'];

/**
 * References
 */
$GLOBALS['TL_LANG']['tl_dc_dive_course']['itemCategory'] = [
    'basic' => 'Grundkurs',
    'specialty' => 'Spezialkurse',
    'mixgas' => 'Mischgastauchen',
    'professional' => 'Professionell'
];

$GLOBALS['TL_LANG']['tl_dc_dive_course']['itemCourseType'] = [
    'try' => 'Schnuppertauchen',
    'basic' => 'GDL Pool Diver (DTSA Grundtauchschein)',
    'gdlsd' => 'GDL* Sports Diver (DTSA*)',
    'gdlasd' => 'GDL** Advanced Sports Diver (DTSA**)',
    'gdldl' => 'GDL*** Dive Leader (DTSA***)',
    'gdldd' => 'GDL Deep Diver (SK Tiefer Tauchen)',
    'gdlgl' => 'GDL Dive Group Leader (AK Gruppenführung)',
    'gdldsd' => 'GDL Dry Suit Diver (SK Trockentauchen)',
    'gdlnavd' => 'GDL Navigation Diver (AK Orientierung beim Tauchen)',
    'gdlnd' => 'GDL Night Diver (AK Nachttauchen)',
    'gdlsard' => 'GDL Safety & Rescue Diver (AK Tauchsicherheit & Rettung)',
    'gdlsrd' => 'GDL Self Rescue Diver (SK Problemlösungen beim Tauchen)',
    'gdknx1' => 'GDL Basic Nitrox Diver (DTSA Nitrox*)',
    'gdlnx2' => 'GDL Advanced Nitrox Diver (DTSA Nitrox**)',
    'gdltb' => 'GDL Advanced Skills Diver (DTSA TEC Basic)',
];

/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_dc_dive_course']['customButton'] = "Custom Routine starten";
