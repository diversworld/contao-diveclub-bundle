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

$GLOBALS['TL_LANG']['tl_dc_check_booking']['booking_legend'] = 'Booking data';
$GLOBALS['TL_LANG']['tl_dc_check_booking']['member_legend'] = 'Customer information';
$GLOBALS['TL_LANG']['tl_dc_check_booking']['notes_legend'] = 'Notes';

$GLOBALS['TL_LANG']['tl_dc_check_booking']['bookingNumber'] = ['Order number', 'Unique number of this order.'];
$GLOBALS['TL_LANG']['tl_dc_check_booking']['bookingDate'] = ['Order date', 'Date and time of the order.'];
$GLOBALS['TL_LANG']['tl_dc_check_booking']['totalPrice'] = ['Total price', 'Total price of the order.'];
$GLOBALS['TL_LANG']['tl_dc_check_booking']['status'] = ['Status', 'Current status of the order.'];
$GLOBALS['TL_LANG']['tl_dc_check_booking']['paid'] = ['Paid', 'Mark the order as paid.'];
$GLOBALS['TL_LANG']['tl_dc_check_booking']['memberId'] = ['Member', 'Assigned member.'];
$GLOBALS['TL_LANG']['tl_dc_check_booking']['firstname'] = ['First name', 'First name of the customer.'];
$GLOBALS['TL_LANG']['tl_dc_check_booking']['lastname'] = ['Last name', 'Last name of the customer.'];
$GLOBALS['TL_LANG']['tl_dc_check_booking']['email'] = ['E-mail', 'E-mail address of the customer.'];
$GLOBALS['TL_LANG']['tl_dc_check_booking']['phone'] = ['Phone', 'Phone number of the customer.'];
$GLOBALS['TL_LANG']['tl_dc_check_booking']['notes'] = ['Notes', 'Additional notes on the order.'];

$GLOBALS['TL_LANG']['tl_dc_check_booking']['status_reference'] = [
    'ordered'   => 'Ordered',
    'delivered' => 'Delivered',
    'checked'   => 'Checked',
    'canceled'  => 'Canceled',
    'pickedup'  => 'Picked up'
];

$GLOBALS['TL_LANG']['tl_dc_check_booking']['paid_reference'] = [
    '0' => 'No',
    '1' => 'Yes'
];

$GLOBALS['TL_LANG']['tl_dc_check_booking']['pdfButton'] = 'Generate PDF';
