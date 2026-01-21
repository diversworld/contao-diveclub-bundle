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

namespace Diversworld\ContaoDiveclubBundle\InsertTag;

use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\InsertTagResolverNestedResolvedInterface;
use Contao\Date;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckBookingModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckOrderModel;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsInsertTag('booking')]
class DcCheckBookingInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return new InsertTagResult('', OutputType::text);
        }

        // Try to get booking ID from request attributes (for the PDF controller)
        $bookingId = $request->attributes->get('id');

        // Fallback: Try to get order ID from session and find its booking
        if (!$bookingId) {
            $orderId = $request->getSession()->get('last_tank_check_order');
            if ($orderId) {
                $order = DcCheckOrderModel::findByPk($orderId);
                if ($order) {
                    $bookingId = $order->pid;
                }
            }
        }

        if (!$bookingId) {
            return new InsertTagResult('', OutputType::text);
        }

        $booking = DcCheckBookingModel::findByPk($bookingId);

        if (null === $booking) {
            return new InsertTagResult('', OutputType::text);
        }

        $property = $insertTag->getParameters()->get(0);

        if (!$property) {
            return new InsertTagResult('', OutputType::text);
        }

        $value = $booking->$property;

        if (null === $value) {
            return new InsertTagResult('', OutputType::text);
        }

        // Handle price fields
        if ($property === 'totalPrice') {
            $value = number_format((float) $value, 2, ',', '.') . ' €';
        }

        if (in_array($property, ['paid', 'status'], true)) {
            Controller::loadLanguageFile('tl_dc_check_booking');
        }

        if ($property === 'paid') {
            $value = $GLOBALS['TL_LANG']['tl_dc_check_booking']['paid_reference'][$value ? '1' : '0'] ?? ($value ? 'Ja' : 'Nein');
        }

        // Handle status field
        if ($property === 'status') {
            $value = $GLOBALS['TL_LANG']['tl_dc_check_booking']['status_reference'][$value] ?? $value;
        }

        // Handle date fields
        if (in_array($property, ['tstamp', 'bookingDate'], true)) {
            $value = Date::parse(Config::get('datimFormat'), (int) $value);
        }

        return new InsertTagResult((string) $value, OutputType::text);
    }
}
