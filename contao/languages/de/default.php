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
 * Miscellaneous
 */
//$GLOBALS['TL_LANG']['MSC'][''] = '';

/**
 * Errors
 */
//$GLOBALS['TL_LANG']['ERR'][''] = '';
$GLOBALS['TL_LANG']['ERR']['aliasNumeric'] = 'Der Alias "%s" darf nicht nur aus Zahlen bestehen.';
$GLOBALS['TL_LANG']['ERR']['aliasExists'] = 'Der Alias "%s" existiert bereits.';
$GLOBALS['TL_LANG']['ERR']['templateContent'] = 'Failed to parse template content into an array: ';
$GLOBALS['TL_LANG']['ERR']['templateNotFound'] = 'Template file not found';

$GLOBALS['TL_LANG']['MSC']['reservationCheckbox'] = 'reservieren';

// Frontend-Labels: Meine Tauchkurse (Modul dc_student_courses)
$GLOBALS['TL_LANG']['MSC']['dc_student_courses'] = [
    'headline' => 'Meine Tauchkurse',
    'noStudent' => 'Kein verknüpfter Tauchschüler gefunden.',
    'noCourses' => 'Für Sie sind derzeit keine Tauchkurse gespeichert.',
    'course' => 'Kurs',
    'status' => 'Status',
    'registered_on' => 'Angemeldet am',
    'payed' => 'Bezahlt',
    'brevet' => 'Brevet erteilt',
    'dateBrevet' => 'Brevet am',
    'dateStart' => 'Beginn',
    'dateEnd' => 'Ende',
    'view_progress' => 'Kursfortschritt anzeigen',
];

// Frontend-Labels: Anmeldung Kursveranstaltung (Gast-Formular)
$GLOBALS['TL_LANG']['MSC']['dc_event_signup'] = [
    'headline' => 'Anmeldung zur Kursveranstaltung',
    'firstname' => 'Vorname',
    'lastname' => 'Nachname',
    'email' => 'E-Mail',
    'phone' => 'Telefon',
    'birthdate' => 'Geburtsdatum',
    'privacy' => 'Ich stimme der Verarbeitung meiner Daten zu.',
    'submit' => 'Jetzt anmelden',
    'msg_success' => 'Erfolgreich zur Veranstaltung angemeldet.',
    'msg_exists' => 'Sie sind bereits für diese Veranstaltung angemeldet.',
    'err_token' => 'Ungültiges Request-Token. Bitte Seite neu laden und erneut versuchen.',
    'err_firstname' => 'Bitte geben Sie Ihren Vornamen ein.',
    'err_lastname' => 'Bitte geben Sie Ihren Nachnamen ein.',
    'err_email' => 'Bitte geben Sie eine gültige E-Mail ein.',
    'err_privacy' => 'Bitte akzeptieren Sie die Datenschutzbestimmungen.',
    'err_general' => 'Ihre Anmeldung konnte nicht verarbeitet werden.'
];

// Frontend-Labels: Tank Check
$GLOBALS['TL_LANG']['MSC']['dc_tank_check'] = [
    'booking_headline' => 'Prüfung buchen für Angebot: %s',
    'contact_data' => 'Ihre Kontaktdaten',
    'logged_in_member' => 'Angemeldetes Mitglied:',
    'firstname' => 'Vorname',
    'lastname' => 'Nachname',
    'email' => 'E-Mail',
    'phone' => 'Telefon',
    'tank_details' => 'Angaben zur Flasche',
    'select_tank' => 'Ihre Flasche wählen',
    'please_select' => '-- Bitte wählen --',
    'serial_number' => 'Seriennummer',
    'manufacturer' => 'Hersteller',
    'baz_number' => 'BAZ-Nummer',
    'tank_size' => 'Flaschengröße',
    'serial_number_label' => 'Seriennummer',
    'manufacturer_label' => 'Hersteller',
    'baz_number_label' => 'BAZ Nummer',
    'tank_size_label' => 'Volumen',
    'tank_info_label' => 'Informationen',
    'o2_clean_label' => 'Sauerstoffrein',
    'other_articles_label' => 'Weitere Artikel',
    'notes_label' => 'Bemerkungen',
    'total_price_label' => 'Gesamtpreis',
    'other_tank_data' => 'Sonstige Angaben zur Flasche',
    'o2_clean' => 'O2-clean',
    'additional_articles' => 'Zusätzliche Artikel/Leistungen',
    'notes' => 'Anmerkungen',
    'total_price' => 'Gesamtpreis',
    'submit_booking' => 'Jetzt verbindlich buchen',
    'success_msg' => 'Vielen Dank! Ihre Buchung für <strong>%s €</strong> wurde erfolgreich gespeichert.',
    'back_to_list' => 'Zurück zur Übersicht',
    'cancel' => 'Abbrechen',
    'no_proposals' => 'Keine Angebote vorhanden',
    'event_date' => 'Prüfungstermin',
    'proposal_date' => 'Angebotsdatum',
    'vendor' => 'Prüfungsunternehmen',
    'book_now' => 'Jetzt buchen',
    'add_tank' => 'Flasche vormerken',
    'or_add_new_tank' => 'Oder eine neue Flasche erfassen:',
    'remove_tank' => 'Entfernen',
    'tank_nr' => 'Flasche Nr. %s',
    'added_to_session' => 'Flasche wurde vorgemerkt.',
    'removed_from_session' => 'Flasche wurde entfernt.',
    'empty_session' => 'Noch keine Flaschen vorgemerkt.',
    'summary_headline' => 'Vorgemerkte Flaschen',
    'no_articles_available' => 'Keine optionalen Artikel vorhanden.',
    'save_tank_label' => 'Flasche dauerhaft in meinem Profil speichern',
];
