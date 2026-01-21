<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_dc_check_order', target: 'config.onsubmit')]
class OrderStatusPickedUpListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function __invoke(DataContainer $dc): void
    {
        if (!$dc->activeRecord || $dc->activeRecord->status !== 'pickedup') {
            return;
        }

        $serialNumber = $dc->activeRecord->serialNumber;
        $bookingId = $dc->activeRecord->bookingId ?: $dc->activeRecord->pid; // Use bookingId if available, else pid (which is bookingNumber)

        if (!$serialNumber) {
            return;
        }

        // Find the tank by serial number
        $tank = $this->connection->fetchAssociative(
            'SELECT id FROM tl_dc_tanks WHERE serialNumber = ?',
            [$serialNumber]
        );

        if (!$tank) {
            return;
        }

        $today = new \DateTime();
        $todayTimestamp = $today->getTimestamp();

        $nextCheck = clone $today;
        $nextCheck->modify('+2 years');
        $nextCheckTimestamp = $nextCheck->getTimestamp();

        $this->connection->update(
            'tl_dc_tanks',
            [
                'lastCheckDate' => $todayTimestamp,
                'nextCheckDate' => $nextCheckTimestamp,
                'lastOrder' => (string) $bookingId
            ],
            ['id' => $tank['id']]
        );
    }
}
