<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;

#[AsCallback(table: 'tl_dc_check_booking', target: 'config.oncreate')]
class BookingNumberGeneratorListener
{
    public function __invoke(string $table, int $insertId, array $set, DataContainer $dc): void
    {
        if ('tl_dc_check_booking' !== $table) {
            return;
        }

        $bookingNumber = 'TC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $dc->Database->prepare("UPDATE tl_dc_check_booking SET bookingNumber=? WHERE id=?")
            ->execute($bookingNumber, $insertId);
    }
}
