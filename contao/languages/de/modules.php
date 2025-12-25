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

//$GLOBALS['TL_LANG']['MOD']['diveclub']                  = ['Tauchclub Manager', 'Verwalten von Equipment, Tauchkursen, usw.'];

$GLOBALS['TL_LANG']['MOD']['dc_course_collection'] = ['Tauchkurse', 'Verwalten von Tauchkursen'];
$GLOBALS['TL_LANG']['MOD']['dc_regulators_collection'] = ['Atemregler', 'Verwalten der Atemregler'];
$GLOBALS['TL_LANG']['MOD']['dc_tanks_collection'] = ['Tauchgeräte', 'Verwalten von Tauchgeräten'];
$GLOBALS['TL_LANG']['MOD']['dc_check_collection'] = ['TÜV Angebote', 'Verwalten der Angebote für TÜV Prüfungen'];
$GLOBALS['TL_LANG']['MOD']['dc_equipment_collection'] = ['Equipment', 'Verwalten von Ausrüstung'];
$GLOBALS['TL_LANG']['MOD']['dc_config_collection'] = ['Einstellungen', 'Einstellungen für den Diveclub Manager'];
$GLOBALS['TL_LANG']['MOD']['dc_reservation_collection'] = ['Reservierungen', 'Verwalten der Reservierungen für Ausrüstung'];
$GLOBALS['TL_LANG']['MOD']['dc_dive_module_collection'] = ['Module', 'Verwalten der Kursmodule'];
$GLOBALS['TL_LANG']['MOD']['dc_dive_student_collection'] = ['Tauchschüler', 'Verwalten der Tauchschüler'];
$GLOBALS['TL_LANG']['MOD']['dc_old_equipment_collection'] = ['Alte Equipment Tabellen', 'Alte Tabellen'];

$GLOBALS['TL_LANG']['FMD']['dc_modules'] = ['Tauchclubmanager', 'Module des Tauchclubmanagers'];
$GLOBALS['TL_LANG']['FMD']['dc_listing'] = ['Angebotsdetails', 'Diveclub Manager'];
$GLOBALS['TL_LANG']['FMD']['dc_tanks_listing'] = ['Tauchgeräte', 'Liste der erfassten Tauchgeräte'];
$GLOBALS['TL_LANG']['FMD']['dc_equipment_listing'] = ['Ausrüstung', 'Liste der erfassten Ausrüstung'];
$GLOBALS['TL_LANG']['FMD']['dc_booking'] = ['Ausrüstungsverleih', 'Vereinsausrüstung ausleihen.'];

// Diveclub FE: Tauchschüler-Kurse
$GLOBALS['TL_LANG']['FMD']['dc_student_courses'] = ['Meine Tauchkurse', 'Zeigt dem angemeldeten Mitglied bzw. verknüpften Tauchschüler seine Tauchkurse an.'];
$GLOBALS['TL_LANG']['FMD']['dc_student_courses_labels'] = [
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
];
