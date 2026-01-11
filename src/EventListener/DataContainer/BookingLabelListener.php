<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;

#[AsCallback(table: 'tl_dc_check_booking', target: 'list.label.label')]
class BookingLabelListener
{
    public function __invoke(array $row, string $label, DataContainer $dc, array $args = null): string|array
    {
        if (null !== $args) {
            $args[0] = $row['bookingNumber'];
            $args[1] = $row['lastname'];
            $args[2] = $row['firstname'];
            $args[3] = number_format((float) $row['totalPrice'], 2, ',', '.') . ' €';

            return $args;
        }

        return sprintf(
            '[%s] %s, %s - %s €',
            $row['bookingNumber'],
            $row['lastname'],
            $row['firstname'],
            number_format((float) $row['totalPrice'], 2, ',', '.')
        );
    }
}
