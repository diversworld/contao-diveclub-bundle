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

// Frontend module: Additional field for course events list
$GLOBALS['TL_LANG']['tl_module']['dc_reader_article'] = ['Target article (Event reader)', 'Please select the article where the event reader is placed.'];

// Optional: Group/Legend texts
$GLOBALS['TL_LANG']['tl_module']['config_legend'] = 'Configuration';

$GLOBALS['TL_LANG']['tl_module']['reg_notification'] = ['Notification to', 'Please enter one or more email addresses for the booking confirmation.'];
$GLOBALS['TL_LANG']['tl_module']['reg_subject']      = ['Email subject', 'Please enter the subject for the confirmation email.'];
$GLOBALS['TL_LANG']['tl_module']['reg_text']         = ['Email text', 'Please enter the text for the confirmation email. You can use insert tags.'];
$GLOBALS['TL_LANG']['tl_module']['confirmation_text'] = ['Confirmation text', 'Please enter the text to be displayed on the confirmation page. You can use insert tags.'];
$GLOBALS['TL_LANG']['tl_module']['showCourseEvents']  = ['Show course events', 'Select this option to show course events in the list.'];
$GLOBALS['TL_LANG']['tl_module']['showTankChecks']   = ['Show tank checks', 'Select this option to show tank checks (TÜV) in the list.'];
$GLOBALS['TL_LANG']['tl_module']['tankCheckJumpTo']  = ['Tank check reader page', 'Please select the page on which the tank check (TÜV) module is integrated.'];
$GLOBALS['TL_LANG']['tl_module']['courseJumpTo'] = ['Kursbestätigung-Seite', 'Wählen Sie die Seite aus, auf der die Bestätigung der Kursbuchung'];

$GLOBALS['TL_LANG']['tl_module']['dc_calendar_view'] = ['Defaultview', 'Choose the default view for the course calendar.'];
$GLOBALS['TL_LANG']['tl_module']['dc_calendar_view_options'] = [
    'dayGridMonth' => 'Monthly',
    'timeGridWeek' => 'Weekly',
    'listYear' => 'Yearly (List)'
];
