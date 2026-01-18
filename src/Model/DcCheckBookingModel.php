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

namespace Diversworld\ContaoDiveclubBundle\Model;

use Contao\CoreBundle\DependencyInjection\Attribute\AsModel;
use Contao\Model;

/**
 * Class DcCheckBookingModel
 *
 * @package Diversworld\ContaoDiveclubBundle\Model
 * @property int $id
 * @property int $pid
 * @property int $tstamp
 * @property string $bookingNumber
 * @property int $bookingDate
 * @property float $totalPrice
 * @property string $status
 * @property int $memberId
 * @property string $firstname
 * @property string $lastname
 * @property string $email
 * @property string $phone
 * @property string $notes
 */
#[AsModel(table: 'tl_dc_check_booking')]
class DcCheckBookingModel extends Model
{
    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_dc_check_booking';
}
