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

/**
 * Backend modules
 */

$GLOBALS['TL_LANG']['MOD']['dc_course_collection'] = ['Tauchkurse und Ausbildungsinhalte', 'Verwaltet Kursvorlagen sowie die zugehörigen Ausbildungsmodule und Übungen.'];
$GLOBALS['TL_LANG']['MOD']['dc_regulators_collection'] = ['Atemregler und Wartungen', 'Verwaltet Atemregler sowie deren Prüf-, Wartungs- und Kontrolldaten.'];
$GLOBALS['TL_LANG']['MOD']['dc_tanks_collection'] = ['Tauchflaschen und Prüfdaten', 'Verwaltet Tauchflaschen, Eigentümerdaten und prüfungsrelevante Angaben.'];
$GLOBALS['TL_LANG']['MOD']['dc_check_collection'] = ['Flaschenprüfungen und TÜV-Buchungen', 'Verwaltet Prüfungstermine, Prüfleistungen, Buchungen und einzelne Prüfaufträge.'];
$GLOBALS['TL_LANG']['MOD']['dc_equipment_collection'] = ['Leihausrüstung', 'Verwaltet die ausleihbaren Ausrüstungsgegenstände des Tauchclubs.'];
$GLOBALS['TL_LANG']['MOD']['dc_config_collection'] = ['Diveclub-Grundeinstellungen', 'Verwaltet allgemeine Texte und Einstellungen für Reservierungen und Ausleihe.'];
$GLOBALS['TL_LANG']['MOD']['dc_reservation_collection'] = ['Ausrüstungsreservierungen', 'Verwaltet Reservierungen und die darin gebuchten Ausrüstungsgegenstände.'];
$GLOBALS['TL_LANG']['MOD']['dc_dive_module_collection'] = ['Ausbildungsmodule und Übungen', 'Verwaltet die Ausbildungsabschnitte und Übungen der Tauchkurse.'];
$GLOBALS['TL_LANG']['MOD']['dc_dive_student_collection'] = ['Tauchschüler und Kursfortschritt', 'Verwaltet Tauchschüler, Kurszuweisungen und absolvierte Übungen.'];
$GLOBALS['TL_LANG']['MOD']['dc_course_event_collection'] = ['Kursveranstaltungen und Terminpläne', 'Verwaltet konkrete Kursveranstaltungen, Ausbildungstermine und deren Übungen.'];

/**
 * Frontend modules
 */
$GLOBALS['TL_LANG']['FMD']['dc_course_instructor'] = ['Instruktorenbereich – Kursdurchführung', 'Ermöglicht Instruktoren die Bearbeitung von Kursveranstaltungen, Terminplänen und Übungsfortschritten.'];
$GLOBALS['TL_LANG']['FMD']['dc_manager'] = ['Diveclub-Frontendmodule', 'Frontendmodule für Ausbildung, Ausrüstungsverleih und Flaschenprüfungen.'];
$GLOBALS['TL_LANG']['FMD']['dc_listing'] = ['Flaschenprüfung – Angebotsdetails', 'Zeigt zu einem TÜV-Termin das Prüfangebot und die auswählbaren Prüfleistungen an.'];
$GLOBALS['TL_LANG']['FMD']['dc_tanks_listing'] = ['Tauchflaschen – Bestandsliste', 'Zeigt eine Übersicht der im Diveclub Manager erfassten Tauchflaschen an.'];
$GLOBALS['TL_LANG']['FMD']['dc_equipment_listing'] = ['Leihausrüstung – Bestandsliste', 'Zeigt eine Übersicht der im Diveclub Manager erfassten Ausrüstungsgegenstände an.'];
$GLOBALS['TL_LANG']['FMD']['dc_booking'] = ['Ausrüstungsverleih – Reservierung', 'Ermöglicht Mitgliedern die Auswahl und Reservierung verfügbarer Vereinsausrüstung.'];
$GLOBALS['TL_LANG']['FMD']['dc_student_courses'] = ['Mitgliederbereich – Meine Tauchkurse', 'Zeigt dem angemeldeten Mitglied seine zugeordneten Tauchkurse und die Links zum jeweiligen Kursfortschritt an.'];
$GLOBALS['TL_LANG']['FMD']['dc_course_progress'] = ['Tauchschüler – Kursfortschritt', 'Zeigt einem Tauchschüler Ausbildungsmodule, Übungen und den aktuellen Bearbeitungsstand eines zugeordneten Kurses an.'];
$GLOBALS['TL_LANG']['FMD']['dc_course_events_list'] = ['Kursveranstaltungen – Übersicht', 'Listet veröffentlichte Kursveranstaltungen und TÜV-Termine mit Links zu den jeweiligen Detail- oder Buchungsseiten auf.'];
$GLOBALS['TL_LANG']['FMD']['dc_course_event_reader'] = ['Kursveranstaltung – Details und Anmeldung', 'Zeigt Beschreibung und Terminplan einer ausgewählten Kursveranstaltung und ermöglicht die Kursanmeldung.'];
$GLOBALS['TL_LANG']['FMD']['dc_course_event_calendar'] = ['Kursveranstaltung – Terminplan', 'Zeigt die Ausbildungstermine einer ausgewählten Kursveranstaltung als Kalender oder Liste an.'];
$GLOBALS['TL_LANG']['FMD']['dc_training_manager_dashboard'] = ['Ausbildungsleitung – Kursübersicht', 'Zeigt der Ausbildungsleitung eine Übersicht über Kurse, Veranstaltungen, Instruktoren und Ausbildungsinhalte.'];
$GLOBALS['TL_LANG']['FMD']['dc_tank_check'] = ['Flaschenprüfung – Buchungsformular', 'Zeigt veröffentlichte TÜV-Termine und ermöglicht Mitgliedern die Buchung einer oder mehrerer Tauchflaschen zur Prüfung.'];
$GLOBALS['TL_LANG']['FMD']['dc_check_confirmation'] = ['Flaschenprüfung – Buchungsbestätigung', 'Zeigt nach einer erfolgreichen TÜV-Buchung die in der Session gespeicherten Buchungs- und Auftragsdaten an.'];
