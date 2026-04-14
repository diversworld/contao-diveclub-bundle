<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Database;

#[AsCallback(table: 'tl_dc_check_order', target: 'config.oncreate')]
class OrderBookingIdListener
{
    public function __invoke(string $table, int $insertId, array $set, DataContainer $dc): void
    {
        if ('tl_dc_check_order' !== $table) {
            return;
        }

        $db = Database::getInstance();

        // pid der neuen Order holen, um den Elterneintrag zu finden
        $objOrder = $db->prepare("SELECT pid FROM tl_dc_check_order WHERE id=?")
            ->limit(1)
            ->execute($insertId);

        if ($objOrder->numRows < 1) {
            return;
        }

        // Buchungsnummer aus tl_dc_check_booking holen
        $objBooking = $db->prepare("SELECT bookingNumber FROM tl_dc_check_booking WHERE id=?")
            ->limit(1)
            ->execute($objOrder->pid);

        if ($objBooking->numRows > 0 && $objBooking->bookingNumber) {
            // bookingId in tl_dc_check_order mit der bookingNumber aktualisieren
            $db->prepare("UPDATE tl_dc_check_order SET bookingId=? WHERE id=?")
                ->execute($objBooking->bookingNumber, $insertId);
        }
    }
}
