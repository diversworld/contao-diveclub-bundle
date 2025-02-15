<?php

declare(strict_types=1);

/*
 * This file is part of diveclub.
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
$GLOBALS['TL_LANG']['tl_dc_tanks']['title_legend']      = "Basic Information";
$GLOBALS['TL_LANG']['tl_dc_tanks']['details_legend']    = "Details about the dive tank";
$GLOBALS['TL_LANG']['tl_dc_tanks']['notes_legend']      = "Notes";
$GLOBALS['TL_LANG']['tl_dc_tanks']['publish_legend']    = "Publish";

/**
 * Global operations
 */
$GLOBALS['TL_LANG']['tl_dc_tanks']['new'] = ["New", "Add a new item"];

/**
 * Operations
 */
$GLOBALS['TL_LANG']['tl_dc_tanks']['edit']      = "Edit record with ID: %s";
$GLOBALS['TL_LANG']['tl_dc_tanks']['copy']      = "Copy record with ID: %s";
$GLOBALS['TL_LANG']['tl_dc_tanks']['delete']    = "Delete record with ID: %s";
$GLOBALS['TL_LANG']['tl_dc_tanks']['show']      = "View record with ID: %s";
$GLOBALS['TL_LANG']['tl_dc_tanks']['toggle']    = "Publish record with ID: %s";

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_dc_tanks']['title']         = ["Inventory Number", "Enter the inventory number"];
$GLOBALS['TL_LANG']['tl_dc_tanks']['alias']         = ["Alias", "Dive tank alias"];
$GLOBALS['TL_LANG']['tl_dc_tanks']['serialNumber']  = ["Serial Number", "Enter the serial number."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['o2clean']       = ["O2 Clean", "The tank is for oxygen and must be O2 clean."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['manufacturer']  = ["Manufacturer", "Manufacturer of the dive tank."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['bazNumber']     = ["BAZ Number", "BAZ Number."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['lastCheckDate'] = ["Last Inspection", "Date of the last inspection."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['nextCheckDate'] = ["Next Inspection", "Date of the next inspection."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['pid']           = ["Next Inspection Date", "Select the next inspection date."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['size']          = ["Size", "Please select the tank size."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['member']        = ["Owner", "Owner of the dive tank."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['addNotes']      = ["Enter Notes", "Record notes about the dive tank."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['published']     = ["Publish", "Publish the record."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['start']         = ["Show from", "From when should the record be displayed."];
$GLOBALS['TL_LANG']['tl_dc_tanks']['stop']          = ["Show until", "Until when should the record be displayed."];

/**
 * References
 */
$GLOBALS['TL_LANG']['tl_dc_tanks']['sizes'] = [
    '2' => '2 L',
    '3' => '3 L',
    '4' => '4 L',
    '5' => '5 L',
    '7' => '7 L',
    '8' => '8 L',
    '10' => '10 L',
    '12' => '12 L',
    '15' => '15 L',
    '18' => '18 L',
    '20' => '20 L',
    '40' => '40 cft',
    '80' => '80 cft'
];

/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_dc_tanks']['createInvoiceButton'] = "Create invoice";
