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
