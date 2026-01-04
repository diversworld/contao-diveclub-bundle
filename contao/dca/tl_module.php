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
    '{redirect_legend},jumpTo;' .
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},guests,cssID';

// Reader einer Kursveranstaltung
$GLOBALS['TL_DCA']['tl_module']['palettes']['dc_course_event_reader'] =
    '{title_legend},name,headline,type;' .
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


// Hinweis: Das Feld "jumpTo" ist ein Standardfeld von tl_module (Seitenauswahl)
// und muss hier nicht erneut definiert werden. Die Palette oben bindet es ein.
