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
$GLOBALS['TL_LANG']['tl_dc_dive_course']['first_legend'] = "Basic settings";
$GLOBALS['TL_LANG']['tl_dc_dive_course']['details_section'] = "Course details";
$GLOBALS['TL_LANG']['tl_dc_dive_course']['requirenment_section'] = "Requirements";
$GLOBALS['TL_LANG']['tl_dc_dive_course']['image_legend'] = "Image settings";
$GLOBALS['TL_LANG']['tl_dc_dive_course']['publish_legend'] = "Publish";

/**
 * Global operations
 */
$GLOBALS['TL_LANG']['tl_dc_dive_course']['new'] = ["New", "Create a new element"];

/**
 * Operations
 */
$GLOBALS['TL_LANG']['tl_dc_dive_course']['edit'] = "Edit record ID %s";
$GLOBALS['TL_LANG']['tl_dc_dive_course']['copy'] = "Copy record ID %s";
$GLOBALS['TL_LANG']['tl_dc_dive_course']['delete'] = "Delete record ID %s";
$GLOBALS['TL_LANG']['tl_dc_dive_course']['show'] = "View record ID %s";
$GLOBALS['TL_LANG']['tl_dc_dive_course']['modules'] = ['Module management', 'Manage course modules'];

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_dc_dive_course']['title'] = ['Course title', 'Title of the dive course'];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['alias'] = ['Alias', 'The alias is a unique reference that can be called up instead of the numeric ID.'];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['instructor'] = ['Instructor', 'Responsible instructor'];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['course_type'] = ['Course type', 'Type of course (e.g. OWD, AOWD, Rescue)'];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['max_participants'] = ["Max. participants", "Maximum number of participants"];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['price'] = ['Price', 'Participation fee'];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['dateStart'] = ["Course start", "Date on which the course begins."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['dateEnd'] = ["Course end", "Date on which the course ends."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['category'] = ["Category", "Select the course category."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['description'] = ["Description", "Please provide the course description."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['requirements'] = ["Prerequisites", "Enter the prerequisites for course participation."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['addImage'] = ["Add image", "You can activate the image settings here."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['fullsize'] = ["Fullsize", "Open the image in fullsize."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['size'] = ["Image size", "Enter the image size."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['floating'] = ["Alignment", "Where should the image be displayed."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['overwriteMeta'] = ["Overwrite metadata", "Overwrite the metadata of the image."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['singleSRC'] = ["Source file", "Please select a file from the file manager."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['alt'] = ["Alternative text", "Here you can enter an alternative text for the image (alt attribute)."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['imageTitle'] = ["Image title", "Here you can enter the title of the image (title attribute)."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['caption'] = ["Caption", "Here you can enter a caption."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['imageUrl'] = ["Image link address", "Here you can enter a web address to which the image should be linked."];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['published'] = ['Published', 'Mark the dive course as published.'];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['start'] = ['Start date', 'Enter a start date.'];
$GLOBALS['TL_LANG']['tl_dc_dive_course']['stop'] = ['End date', 'Enter an end date.'];

/**
 * References
 */
$GLOBALS['TL_LANG']['tl_dc_dive_course']['itemCategory'] = [
    'basic' => 'Basic Course',
    'specialty' => 'Specialty Courses',
    'mixgas' => 'Mixed Gas Diving',
    'professional' => 'Professional'
];

$GLOBALS['TL_LANG']['tl_dc_dive_course']['itemCourseType'] = [
    'try' => 'Try Scuba',
    'basic' => 'GDL Pool Diver (DTSA Basic)',
    'gdlsd' => 'GDL* Sports Diver (DTSA*)',
    'gdlasd' => 'GDL** Advanced Sports Diver (DTSA**)',
    'gdldl' => 'GDL*** Dive Leader (DTSA***)',
    'gdldd' => 'GDL Deep Diver',
    'gdlgl' => 'GDL Dive Group Leader',
    'gdldsd' => 'GDL Dry Suit Diver',
    'gdlnavd' => 'GDL Navigation Diver',
    'gdlnd' => 'GDL Night Diver',
    'gdlsard' => 'GDL Safety & Rescue Diver',
    'gdlsrd' => 'GDL Self Rescue Diver',
    'gdknx1' => 'GDL Basic Nitrox Diver (DTSA Nitrox*)',
    'gdlnx2' => 'GDL Advanced Nitrox Diver (DTSA Nitrox**)',
    'gdltb' => 'GDL Advanced Skills Diver (DTSA TEC Basic)',
];

/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_dc_dive_course']['customButton'] = "Start custom routine";
