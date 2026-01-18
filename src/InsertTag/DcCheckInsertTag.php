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
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\InsertTagResolverNestedResolvedInterface;
use Contao\Date;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckBookingModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckOrderModel;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsInsertTag('dc_check')]
class DcCheckInsertTag implements InsertTagResolverNestedResolvedInterface
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

        $orderId = $request->getSession()->get('last_tank_check_order');

        if (!$orderId) {
            return new InsertTagResult('', OutputType::text);
        }

        $order = DcCheckOrderModel::findByPk($orderId);

        if (null === $order) {
            return new InsertTagResult('', OutputType::text);
        }

        $property = $insertTag->getParameters()->get(0);

        if (!$property) {
            return new InsertTagResult('', OutputType::text);
        }

        $value = null;

        // Check order properties first
        if (isset($order->$property) && $order->$property !== null) {
            $value = $order->$property;
        } else {
            // Check booking (parent) properties
            $booking = DcCheckBookingModel::findByPk($order->pid);

            if (null !== $booking && isset($booking->$property) && $booking->$property !== null) {
                $value = $booking->$property;
            }
        }

        if (null === $value) {
            return new InsertTagResult('', OutputType::text);
        }

        // Handle date fields
        if (in_array($property, ['tstamp', 'bookingDate'], true)) {
            $value = Date::parse(Config::get('datimFormat'), (int) $value);
        }

        return new InsertTagResult((string) $value, OutputType::text);
    }
}
