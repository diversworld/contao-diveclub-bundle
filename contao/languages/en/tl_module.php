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

// Dedicated redirect fields
$GLOBALS['TL_LANG']['tl_module']['courseProgressJumpTo'] = ['Target page for course progress', 'Select the page containing the “Course progress” frontend module. Without this target page, the course overview does not generate progress links.'];
$GLOBALS['TL_LANG']['tl_module']['courseConfirmationJumpTo'] = ['Confirmation page after course registration', 'Select the page shown after a successful course registration. No additional frontend module is required on the target page; registration data can be displayed there using {{course::*}} insert tags. Without a target page, the user remains on the reader page.'];
$GLOBALS['TL_LANG']['tl_module']['tankConfirmationJumpTo'] = ['Target page for the tank-check booking confirmation', 'Select the page containing the “Booking confirmation (TÜV)” frontend module. The booking data is stored in the session before redirecting. Without a target page, the booking module displays the confirmation itself.'];
$GLOBALS['TL_LANG']['tl_module']['tankCheckJumpTo'] = ['Target page for tank-check bookings', 'Select the page containing the “Tank check (TÜV)” frontend module. Links for published tank-check dates lead to this booking page.'];
$GLOBALS['TL_LANG']['tl_module']['courseJumpTo'] = ['Target page for course events', 'Select the page containing the “Course event (reader)” frontend module. Links for published course events lead to this detail and registration page.'];

// Calendar view
$GLOBALS['TL_LANG']['tl_module']['dc_calendar_view'] = ['Defaultview', 'Choose the default view for the course calendar.'];
$GLOBALS['TL_LANG']['tl_module']['dc_calendar_view_options'] = [
    'dayGridMonth' => 'Monthly',
    'timeGridWeek' => 'Weekly',
    'listYear' => 'Yearly (List)'
];
