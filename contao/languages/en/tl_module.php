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
$GLOBALS['TL_LANG']['tl_module']['config_legend'] = 'Configuration';
$GLOBALS['TL_LANG']['tl_module']['dc_progress_redirect_legend'] = 'Course progress redirect';
$GLOBALS['TL_LANG']['tl_module']['dc_course_confirmation_redirect_legend'] = 'Redirect after course registration';
$GLOBALS['TL_LANG']['tl_module']['dc_tank_confirmation_redirect_legend'] = 'Redirect after tank-check booking';

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_module']['dc_reader_article'] = ['Target article (Event reader)', 'Please select the article where the event reader is placed.'];
$GLOBALS['TL_LANG']['tl_module']['reg_notification'] = ['Notification to', 'Please enter one or more email addresses for the booking confirmation.'];
$GLOBALS['TL_LANG']['tl_module']['reg_subject']      = ['Email subject', 'Please enter the subject for the confirmation email.'];
$GLOBALS['TL_LANG']['tl_module']['reg_text']         = ['Email text', 'Please enter the text for the confirmation email. You can use insert tags.'];
$GLOBALS['TL_LANG']['tl_module']['confirmation_text'] = ['Confirmation text', 'Please enter the text to be displayed on the confirmation page. You can use insert tags.'];
$GLOBALS['TL_LANG']['tl_module']['showCourseEvents']  = ['Show course events', 'Select this option to show course events in the list.'];
$GLOBALS['TL_LANG']['tl_module']['showTankChecks']   = ['Show tank checks', 'Select this option to show tank checks (TÜV) in the list.'];

// Redirect fields (jumpTo)
$GLOBALS['TL_LANG']['tl_module']['dc_progress_jumpTo'] = ['Course progress page', 'Select the page containing the Course progress frontend module (dc_course_progress). Each course assignment in the student course list links to this page with its assignment ID.'];
$GLOBALS['TL_LANG']['tl_module']['dc_course_confirmation_jumpTo'] = ['Course registration confirmation page', 'Select the page shown after a successful course-event registration. The {{course::*}} insert tags can be used on this page.'];
$GLOBALS['TL_LANG']['tl_module']['dc_tank_confirmation_jumpTo'] = ['Tank-check booking confirmation page', 'Select the page containing the Tank-check booking confirmation frontend module (dc_check_confirmation). The booking data is stored in the session before redirecting.'];
$GLOBALS['TL_LANG']['tl_module']['tankCheckJumpTo'] = ['Tank-check booking page', 'Select the page containing the Tank check frontend module (dc_tank_check). Links for published tank-check dates lead to this page.'];
$GLOBALS['TL_LANG']['tl_module']['courseJumpTo'] = ['Course-event detail page', 'Select the page containing the Course event reader frontend module (dc_course_event_reader). Links for published course events lead to this page.'];

// Calendar view
$GLOBALS['TL_LANG']['tl_module']['dc_calendar_view'] = ['Defaultview', 'Choose the default view for the course calendar.'];
$GLOBALS['TL_LANG']['tl_module']['dc_calendar_view_options'] = [
    'dayGridMonth' => 'Monthly',
    'timeGridWeek' => 'Weekly',
    'listYear' => 'Yearly (List)'
];
