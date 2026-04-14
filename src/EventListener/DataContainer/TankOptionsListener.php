<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Database;

class TankOptionsListener
{
    #[AsCallback(table: 'tl_dc_check_order', target: 'fields.tankId.options')]
    public function __invoke(DataContainer $dc): array
    {
        $options = [];

        if (!$dc->activeRecord) {
            return $options;
        }

        // 1. Die memberId der übergeordneten Buchung holen
        // tl_dc_check_order.pid -> tl_dc_check_booking.id
        $booking = Database::getInstance()
            ->prepare("SELECT memberId FROM tl_dc_check_booking WHERE id=?")
            ->execute($dc->activeRecord->pid);

        if ($booking->numRows < 1 || !$booking->memberId) {
            return $options;
        }

        $memberId = (int)$booking->memberId;

        // 2. Flaschen dieses Mitglieds laden
        $tanks = Database::getInstance()
            ->prepare("SELECT id, title, serialNumber FROM tl_dc_tanks WHERE owner=? ORDER BY title")
            ->execute($memberId);

        while ($tanks->next()) {
            $options[$tanks->id] = sprintf('%s (SN: %s)', $tanks->title, $tanks->serialNumber);
        }

        return $options;
    }
}
