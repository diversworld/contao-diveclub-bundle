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
$GLOBALS['TL_LANG']['tl_dc_reservation']['title_legend']        = "Basic information";
$GLOBALS['TL_LANG']['tl_dc_reservation']['details_legend']      = "Details about the diving equipment";
$GLOBALS['TL_LANG']['tl_dc_reservation']['reservation_legend']  = "Reservation";
$GLOBALS['TL_LANG']['tl_dc_reservation']['publish_legend']      = "Publishing";

/**
 * Global operations
 */
$GLOBALS['TL_LANG']['tl_dc_reservation']['new']         = ["New", "Create a new element"];

/**
 * Operations
 */
$GLOBALS['TL_LANG']['tl_dc_reservation']['edit']        = "Edit record with ID: %s";
$GLOBALS['TL_LANG']['tl_dc_reservation']['copy']        = "Copy record with ID: %s";
$GLOBALS['TL_LANG']['tl_dc_reservation']['delete']      = "Delete record with ID: %s";
$GLOBALS['TL_LANG']['tl_dc_reservation']['show']        = "View record with ID: %s";
$GLOBALS['TL_LANG']['tl_dc_reservation']['toggle']      = "Publish record with ID: %s";

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_dc_reservation']['title']               = ["Reservation", "Reservation process number"];
$GLOBALS['TL_LANG']['tl_dc_reservation']['alias']               = ["Alias", "Bottle alias"];
$GLOBALS['TL_LANG']['tl_dc_reservation']['serialNumber']        = ["Serial number", "Enter the serial number."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['o2clean']             = ["O2 Clean", "The bottle is suitable for oxygen and must be O2 clean."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['manufacturer']        = ["Manufacturer", "Manufacturer of the diving equipment."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['bazNumber']           = ["BAZ number", "BAZ number."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['checkId']     	    = ["Inspection date", "Date of the last inspection."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['owner']               = ["Owner", "Owner of the diving equipment."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['lastCheckDate']       = ["Last inspection", "Date of the last inspection."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['nextCheckDate']       = ["Next inspection", "Date of the next inspection."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['pid']                 = ["Next inspection date", "Select the next inspection date."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['size']                = ["Size", "Please select the bottle size."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['member']              = ["Owner", "Owner of the bottle."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['rentalFee']           = ["Rental fee", "Fee for the usage of the equipment."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['addNotes']            = ["Add remarks", "Record remarks about the diving equipment."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['published']           = ["Publish", "Publish the record."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['start']               = ["Show from", "From when the record should be displayed."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['stop']                = ["Show until", "Until when the record should be displayed."];
$GLOBALS['TL_LANG']['tl_dc_reservation']['reservation_status']  = ['Status','Status of the reservation'];
$GLOBALS['TL_LANG']['tl_dc_reservation']['member_id']           = ['Member','Name of the club member'];
$GLOBALS['TL_LANG']['tl_dc_reservation']['reservedFor']         = ['Reserved for','Name of Member for whom the reservation was made'];
$GLOBALS['TL_LANG']['tl_dc_reservation']['reserved_at']         = ['Reserved on','Date of the reservation'];
$GLOBALS['TL_LANG']['tl_dc_reservation']['picked_up_at']        = ['Picked up on','Date of pick-up'];
$GLOBALS['TL_LANG']['tl_dc_reservation']['returned_at']         = ['Returned on','Date of the return'];

/**
 * References
 */
$GLOBALS['TL_LANG']['tl_dc_reservation']['tl_dc_tanks']             = 'Diving equipment';
$GLOBALS['TL_LANG']['tl_dc_reservation']['tl_dc_regulators']        = 'Regulators';
$GLOBALS['TL_LANG']['tl_dc_reservation']['tl_dc_equipment']   = 'Equipment';

$GLOBALS['TL_LANG']['tl_dc_reservation']['itemStatus'] = [
    'avaílable' => 'available',
    'reserved'  => 'reserved',
    'borrowed'  => 'borrowed',
    'returned'  => 'returned',
    'cancelled' => 'cancelled',
    'overdue'   => 'overdue',
    'lost'      => 'lost',
    'damaged'   => 'damaged',
    'missing'   => 'missing',
];

/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_dc_reservation']['createInvoiceButton']         = "Create invoice";
