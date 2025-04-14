<?php

/**
 * Global operations
 */
$GLOBALS['TL_LANG']['tl_dc_equipment']['new']               = ["New", "Create a new element"];

/**
 * Operations
 */
$GLOBALS['TL_LANG']['tl_dc_equipment']['edit']              = "Edit record with ID: %s";
$GLOBALS['TL_LANG']['tl_dc_equipment']['copy']              = "Copy record with ID: %s";
$GLOBALS['TL_LANG']['tl_dc_equipment']['delete']            = "Delete record with ID: %s";
$GLOBALS['TL_LANG']['tl_dc_equipment']['show']              = "View record with ID: %s";

// Legends
$GLOBALS['TL_LANG']['tl_dc_equipment']['title_legend']      = 'Inventory number and alias';
$GLOBALS['TL_LANG']['tl_dc_equipment']['status_legend']     = 'Status and rental fee';
$GLOBALS['TL_LANG']['tl_dc_equipment']['details_legend']    = 'Details';
$GLOBALS['TL_LANG']['tl_dc_equipment']['notes_legend']      = 'Notes';
$GLOBALS['TL_LANG']['tl_dc_equipment']['publish_legend']    = 'Publication';

// Fields
$GLOBALS['TL_LANG']['tl_dc_equipment']['title']             = ['Inventory number', 'Please enter the inventory number of the equipment.'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['alias']             = ['Alias', 'The alias will be automatically generated if no alias is provided.'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['type']              = ['Type', 'Select a type.'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['subType']           = ['Subtype', 'Select a subtype.'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['addNotes']          = ['Remarks', 'Add additional information.'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['manufacturer']      = ['Manufacturer', 'Please select the manufacturer.'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['model']             = ['Model', 'What is the model designation?'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['size']              = ['Size', 'Please select a size.'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['rentalFee']         = ['Price', 'Fee for the item.'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['color']             = ['Color', 'What is the color of the equipment?'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['addNotes']          = ['Additional Notes', 'Add optional notes.'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['serialNumber']      = ['Serial Number', 'Please provide the serial number.'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['buyDate']           = ['Purchase Date', 'Please select the purchase date.'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['notes']             = ['Notes', 'Please enter the notes.'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['published']         = ['Published', 'Mark the equipment as published.'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['start']             = ['Start Date', 'Enter a start date.'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['stop']              = ['End Date', 'Enter an end date.'];

$GLOBALS['TL_LANG']['tl_dc_equipment']['stageType']         = ['1' => 'First Stage', '2' => 'Second Stage'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['regType']           = ['1' => 'Primary', '2' => 'Secondary'];
$GLOBALS['TL_LANG']['tl_dc_equipment']['status']            = ['Status', 'Status of the asset'];

// Reference
$GLOBALS['TL_LANG']['tl_dc_equipment']['itemStatus'] = [
    'available' => 'available',
    'reserved'  => 'reserved',
    'borrowed'  => 'borrowed',
    'returned'  => 'returned',
    'cancelled' => 'cancelled',
    'overdue'   => 'overdue',
    'lost'      => 'lost',
    'damaged'   => 'damaged',
    'missing'   => 'missing',
];
