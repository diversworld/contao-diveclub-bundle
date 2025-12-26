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
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},guests,cssID';

// Liste der Kursveranstaltungen
$GLOBALS['TL_DCA']['tl_module']['palettes']['dc_course_events_list'] =
    '{title_legend},name,headline,type;' .
    '{config_legend},dc_reader_article;' .
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},guests,cssID';

// Reader einer Kursveranstaltung
$GLOBALS['TL_DCA']['tl_module']['palettes']['dc_course_event_reader'] =
    '{title_legend},name,headline,type;' .
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},guests,cssID';

// Felder
$GLOBALS['TL_DCA']['tl_module']['fields']['dc_reader_article'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['dc_reader_article'],
    'exclude' => true,
    'inputType' => 'select',
    'foreignKey' => 'tl_article.title',
    'eval' => ['mandatory' => true, 'chosen' => true, 'includeBlankOption' => true],
    'sql' => "int(10) unsigned NOT NULL default 0",
];
