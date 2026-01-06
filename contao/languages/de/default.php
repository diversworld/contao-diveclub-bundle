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
