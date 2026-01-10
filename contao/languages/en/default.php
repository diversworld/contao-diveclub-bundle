<?php

declare(strict_types=1);

/*
 * This file is part of ContaoDiveclubBundle.
 */

// Errors (optional examples)
$GLOBALS['TL_LANG']['ERR']['aliasNumeric'] = 'The alias "%s" must not be numeric only.';
$GLOBALS['TL_LANG']['ERR']['aliasExists'] = 'The alias "%s" already exists.';

// Misc labels
$GLOBALS['TL_LANG']['MSC']['reservationCheckbox'] = 'reserve';

// Frontend labels: My dive courses (module dc_student_courses)
$GLOBALS['TL_LANG']['MSC']['dc_student_courses'] = [
    'headline' => 'My dive courses',
    'noStudent' => 'No linked student found for your member account.',
    'noCourses' => 'There are currently no dive courses stored for you.',
    'course' => 'Course',
    'status' => 'Status',
    'registered_on' => 'Registered on',
    'payed' => 'Paid',
    'brevet' => 'Brevet issued',
    'dateBrevet' => 'Brevet on',
    'dateStart' => 'Start',
    'dateEnd' => 'End',
    'view_progress' => 'Show progress',
];

// Frontend-Labels: Tank Check
$GLOBALS['TL_LANG']['MSC']['dc_tank_check'] = [
    'booking_headline' => 'Book check for offer: %s',
    'contact_data' => 'Your contact details',
    'logged_in_member' => 'Logged in member:',
    'firstname' => 'First name',
    'lastname' => 'Last name',
    'email' => 'E-mail',
    'phone' => 'Phone',
    'tank_details' => 'Tank details',
    'select_tank' => 'Select your tank',
    'please_select' => '-- Please select --',
    'serial_number' => 'Serial number',
    'manufacturer' => 'Manufacturer',
    'baz_number' => 'BAZ number',
    'tank_size' => 'Tank size (for price calculation)',
    'other_tank_data' => 'Other tank data',
    'o2_clean' => 'O2-clean',
    'additional_articles' => 'Additional articles/services',
    'notes' => 'Notes',
    'total_price' => 'Total price',
    'submit_booking' => 'Book now (binding)',
    'success_msg' => 'Thank you! Your booking for <strong>%s €</strong> has been successfully saved.',
    'back_to_list' => 'Back to overview',
    'cancel' => 'Cancel',
    'no_proposals' => 'No current check dates available.',
    'event_date' => 'Date: %s',
    'proposal_date' => 'Date: %s',
    'vendor' => 'Checker',
    'book_now' => 'Book check now'
];
