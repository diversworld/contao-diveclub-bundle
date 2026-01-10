<?php

declare(strict_types=1);

/*
 * This file is part of ContaoDiveclubBundle.
 *
 * (c) Diversworld, Eckhard Becker 2025 <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

$GLOBALS['TL_LANG']['tl_dc_check_order']['member_legend'] = 'Member information';
$GLOBALS['TL_LANG']['tl_dc_check_order']['tank_legend']   = 'Tank information';
$GLOBALS['TL_LANG']['tl_dc_check_order']['order_legend']  = 'Order details';
$GLOBALS['TL_LANG']['tl_dc_check_order']['notes_legend']  = 'Notes';

$GLOBALS['TL_LANG']['tl_dc_check_order']['memberId']         = ['Member', 'Please select the member.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['firstname']        = ['First name', 'First name of the customer (if not a member).'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['lastname']         = ['Last name', 'Last name of the customer (if not a member).'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['email']            = ['E-mail', 'E-mail address of the customer.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['phone']            = ['Phone', 'Phone number of the customer.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['tankId']           = ['Club tank / Own tank', 'Select a registered tank.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['serialNumber']     = ['Serial number', 'Serial number of the tank.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['manufacturer']     = ['Manufacturer', 'Manufacturer of the tank.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['bazNumber']        = ['BAZ number', 'BAZ number of the tank.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['size']             = ['Tank size', 'Size of the tank in liters.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['o2clean']          = ['O2-clean', 'Is the tank O2-clean?'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['tankData']         = ['Manual tank data', 'Enter data for a new tank (size, serial, manufacturer).'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['selectedArticles'] = ['Selected articles', 'Additional services from the offer.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['totalPrice']       = ['Total price', 'The calculated total price of the check.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['status']           = ['Status', 'Status of the order.'];
$GLOBALS['TL_LANG']['tl_dc_check_order']['notes']            = ['Internal notes', 'Additional comments on the check.'];

$GLOBALS['TL_LANG']['tl_dc_check_order']['sizes'] = [
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

$GLOBALS['TL_LANG']['tl_dc_check_order']['status_reference'] = [
    'ordered'   => 'Ordered',
    'delivered' => 'Delivered',
    'checked'   => 'Checked',
    'canceled'  => 'Canceled'
];
