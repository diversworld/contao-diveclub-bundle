<?php

declare(strict_types=1);

/*
 * DCA: tl_module (Erweiterung für Diveclub-Frontend-Module)
 *
 * Ergänzt das FE-Modul "dc_student_courses" um die Template-Auswahl (customTpl).
 */

// Palette für das eigene Frontend-Modul registrieren, inkl. Template-Auswahl
$GLOBALS['TL_DCA']['tl_module']['palettes']['dc_student_courses'] =
    '{title_legend},name,headline,type;' .
    '{redirect_legend},jumpTo;' .
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},guests,cssID';

// Fortschritt einer Kurs-Zuweisung
$GLOBALS['TL_DCA']['tl_module']['palettes']['dc_course_progress'] =
    '{title_legend},name,headline,type;' .
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},guests,cssID';

// Liste der Kursveranstaltungen
$GLOBALS['TL_DCA']['tl_module']['palettes']['dc_course_events_list'] =
    '{title_legend},name,headline,type;' .
    '{config_legend},jumpTo,tankCheckJumpTo,showCourseEvents,showTankChecks;' .
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},guests,cssID';

// Reader einer Kursveranstaltung
$GLOBALS['TL_DCA']['tl_module']['palettes']['dc_course_event_reader'] =
    '{title_legend},name,headline,type;' .
    '{redirect_legend},jumpTo;' .
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},guests,cssID';

// Kalender für Kursveranstaltungen
$GLOBALS['TL_DCA']['tl_module']['palettes']['dc_course_event_calendar'] =
    '{title_legend},name,headline,type;' .
    '{config_legend},dc_calendar_view;' .
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['dc_booking'] =
    '{title_legend},name,headline,type;' .
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['dc_listing'] =
    '{title_legend},name,headline,type;' .
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['dc_equipment_listing'] =
    '{title_legend},name,headline,type;' .
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['dc_tanks_listing'] =
    '{title_legend},name,headline,type;' .
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['dc_tank_check'] =
    '{title_legend},name,headline,type;' .
    '{config_legend},jumpTo,reg_notification,reg_subject,reg_text;' .
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['dc_check_confirmation'] =
    '{title_legend},name,headline,type;' .
    '{config_legend},confirmation_text;' .
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},guests,cssID';

$GLOBALS['TL_DCA']['tl_module']['palettes']['dc_course_instructor'] =
    '{title_legend},name,headline,type;' .
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},guests,cssID';

// Felder für die Kursliste
$GLOBALS['TL_DCA']['tl_module']['fields']['showCourseEvents'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['showCourseEvents'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default '1'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['showTankChecks'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['showTankChecks'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['tankCheckJumpTo'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['tankCheckJumpTo'],
    'exclude'                 => true,
    'inputType'               => 'pageTree',
    'foreignKey'              => 'tl_page.title',
    'eval'                    => ['mandatory' => false, 'fieldType' => 'radio', 'tl_class' => 'clr'],
    'sql'                     => "int(10) unsigned NOT NULL default 0",
    'relation'                => ['type' => 'hasOne', 'load' => 'lazy']
];

// Felder für die Tank-Check E-Mail-Konfiguration (analog zu anderen Modulen falls vorhanden)
$GLOBALS['TL_DCA']['tl_module']['fields']['reg_notification'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['reg_notification'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'emails', 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['reg_subject'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['reg_subject'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['decodeEntities' => true, 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['reg_text'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['reg_text'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['decodeEntities' => true, 'tl_class' => 'clr'],
    'sql'       => "text NULL"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['confirmation_text'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['confirmation_text'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval'      => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
    'sql'       => "text NULL"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['dc_calendar_view'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['dc_calendar_view'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => ['dayGridMonth', 'timeGridWeek', 'listYear'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['dc_calendar_view_options'],
    'eval'      => ['tl_class' => 'w50'],
    'sql'       => "varchar(32) NOT NULL default 'dayGridMonth'"
];

// Hinweis: Das Feld "jumpTo" ist ein Standardfeld von tl_module (Seitenauswahl)
// und muss hier nicht erneut definiert werden. Die Palette oben bindet es ein.
