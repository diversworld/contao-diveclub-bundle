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
$GLOBALS['TL_LANG']['tl_module']['config_legend'] = 'Konfiguration';
$GLOBALS['TL_LANG']['tl_module']['dc_progress_redirect_legend'] = 'Weiterleitung zum Kursfortschritt';
$GLOBALS['TL_LANG']['tl_module']['dc_course_confirmation_redirect_legend'] = 'Weiterleitung nach der Kursanmeldung';
$GLOBALS['TL_LANG']['tl_module']['dc_tank_confirmation_redirect_legend'] = 'Weiterleitung nach der TÜV-Buchung';

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_module']['dc_reader_article'] = ['Ziel-Artikel (Event-Reader)', 'Wählen Sie den Artikel aus, auf dem der Event-Reader platziert ist.'];
$GLOBALS['TL_LANG']['tl_module']['reg_notification'] = ['Benachrichtigung an', 'Geben Sie eine oder mehrere E-Mail-Adressen für die Buchungsbestätigung an.'];
$GLOBALS['TL_LANG']['tl_module']['reg_subject']      = ['E-Mail-Betreff', 'Geben Sie den Betreff der Bestätigungs-E-Mail ein.'];
$GLOBALS['TL_LANG']['tl_module']['reg_text']         = ['E-Mail-Text', 'Geben Sie den Text der Bestätigungs-E-Mail ein. Sie können Insert-Tags verwenden.'];
$GLOBALS['TL_LANG']['tl_module']['confirmation_text'] = ['Bestätigungstext', 'Geben Sie den Text ein, der auf der Bestätigungsseite angezeigt werden soll. Sie können Insert-Tags verwenden.'];
$GLOBALS['TL_LANG']['tl_module']['showCourseEvents']  = ['Tauchkurse anzeigen', 'Wählen Sie diese Option, um Tauchkurse in der Liste anzuzeigen.'];
$GLOBALS['TL_LANG']['tl_module']['showTankChecks']   = ['TÜV-Prüfungen Liste', 'Wählen Sie diese Option, um TÜV-Prüfungen in der Liste anzuzeigen.'];

// Weiterleitungsfelder (jumpTo)
$GLOBALS['TL_LANG']['tl_module']['dc_progress_jumpTo'] = ['Kursfortschrittsseite', 'Wählen Sie die Seite aus, auf der das Frontend-Modul „Kursfortschritt“ (dc_course_progress) eingebunden ist. Die Kursübersicht verlinkt jede Kurszuweisung mit der Parameter-ID auf diese Seite.'];
$GLOBALS['TL_LANG']['tl_module']['dc_course_confirmation_jumpTo'] = ['Bestätigungsseite der Kursanmeldung', 'Wählen Sie die Seite aus, die nach einer erfolgreichen Anmeldung zu einer Kursveranstaltung angezeigt werden soll. Dort können die {{course::*}}-Insert-Tags verwendet werden.'];
$GLOBALS['TL_LANG']['tl_module']['dc_tank_confirmation_jumpTo'] = ['Bestätigungsseite der TÜV-Buchung', 'Wählen Sie die Seite aus, auf der das Frontend-Modul „Buchungsbestätigung (TÜV)“ (dc_check_confirmation) eingebunden ist. Die Buchungsdaten werden vor der Weiterleitung in der Session gespeichert.'];
$GLOBALS['TL_LANG']['tl_module']['tankCheckJumpTo'] = ['Buchungsseite für TÜV-Prüfungen', 'Wählen Sie die Seite aus, auf der das Frontend-Modul „Flaschenprüfung (TÜV)“ (dc_tank_check) eingebunden ist. Links zu veröffentlichten TÜV-Terminen führen auf diese Seite.'];
$GLOBALS['TL_LANG']['tl_module']['courseJumpTo'] = ['Detailseite für Kursveranstaltungen', 'Wählen Sie die Seite aus, auf der das Frontend-Modul „Kursveranstaltung-Reader“ (dc_course_event_reader) eingebunden ist. Links zu veröffentlichten Kursveranstaltungen führen auf diese Seite.'];

// Kalender-Ansicht
$GLOBALS['TL_LANG']['tl_module']['dc_calendar_view'] = ['Standardansicht', 'Wählen Sie die Standardansicht für den Kalender.'];
$GLOBALS['TL_LANG']['tl_module']['dc_calendar_view_options'] = [
    'dayGridMonth' => 'Monatsdarstellung',
    'timeGridWeek' => 'Wochenweise Darstellung',
    'listYear'     => 'Jahresdarstellung (Liste)'
];
