<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_dc_check_booking', target: 'config.onsubmit')]
class BookingStatusSyncListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function __invoke(DataContainer $dc): void
    {
        if (!$dc->activeRecord) {
            return;
        }

        $status = $dc->activeRecord->status;
        $bookingId = $dc->activeRecord->id;

        // Update all related orders with the same status
        $this->connection->update(
            'tl_dc_check_order',
            ['status' => $status],
            ['pid' => $bookingId]
        );
    }
}
