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
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['title_legend']          = "Basic Information";
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['details_legend']        = "Details about the diving equipment";
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['reservation_legend']    = "Reservation";
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['notes_legend']          = "Notes";
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['publish_legend']        = "Publish";

/**
 * Global operations
 */
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['new']                   = ["New", "Create a new item"];

/**
 * Operations
 */
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['edit']                  = "Edit record with ID: %s";
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['copy']                  = "Copy record with ID: %s";
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['delete']                = "Delete record with ID: %s";
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['show']                  = "View record with ID: %s";
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['toggle']                = "Publish record with ID: %s";

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['manufacturer']          = ["Manufacturer", "Manufacturer of the diving equipment."];

$GLOBALS['TL_LANG']['tl_dc_reservation_items']['pid']                   = ["Next inspection date", "Select the next inspection date."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['size']                  = ["Size", "Please select the bottle size."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['member']                = ["Owner", "Owner of the bottle."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['reservation_status']    = ['Status','Reservation status'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['item_type']             = ['Category', 'Please select the type of equipment.'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['item_id']               = ['Selected item', 'Please select the type of equipment.'];

$GLOBALS['TL_LANG']['tl_dc_reservation_items']['types']                 = ['Equipment', 'Please select the type of equipment.'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['sub_type']              = ['Equipment part', 'Please select the equipment part.'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['reserved_at']           = ['Reserved', 'Please specify the reservation date.'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['created_at']            = ['Created', 'The creation date of the reservation is generated automatically.'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['updated_at']            = ['Updated', 'The update date of the reservation is generated automatically.'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['picked_up_at']          = ['Picked up', 'Please specify the pickup date.'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['returned_at']           = ['Returned', 'Please specify the return date.'];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['addNotes']              = ["Enter notes", "Record notes about the diving equipment."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['published']             = ["Publish", "Publish the record."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['start']                 = ["Show from", "From when should the record be displayed."];
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['stop']                  = ["Show until", "Until when should the record be displayed."];

/**
 * Explanations
 */
$GLOBALS['TL_LANG']['XPL']['selected_asset'] = [
    [
        'Note',
        'The status of the currently selected asset will be changed to the <strong>status selected here</strong> if you change the status.'
    ]
];

/**
 * References
 */
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemTypes']             = [
    'tl_dc_tanks'       => 'Diving equipment',
    'tl_dc_regulators'  => 'Breathing regulators',
    'tl_dc_equipment'   => 'Equipment',
];

$GLOBALS['TL_LANG']['tl_dc_reservation_items']['itemStatus']            = [
    'available' => 'available',
    'reserved'  => 'reserved',
    'borrowed'  => 'borrowed',
    'returned'  => 'returned',
    'cancelled' => 'canceled',
    'overdue'   => 'overdue',
    'lost'      => 'lost',
    'damaged'   => 'damaged',
    'missing'   => 'missing',
];

/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_dc_reservation_items']['createInvoiceButton']   = "Create an invoice";
