<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class TankCalendarOptionsListener
{
    private Connection $db;
    private LoggerInterface $logger;

    public function __construct(Connection $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    #[AsCallback(table: 'tl_dc_tanks', target: 'fields.checkId.options')]
    public function __invoke(): array
    {
        $events = $this->db->fetchAllAssociative("SELECT id, title FROM tl_calendar_events WHERE addCheckInfo = '1' and published = '1'");
        $options = [];

        foreach ($events as $event) {
            $this->logger->info('Event-Daten: ', $event);
            $options[$event['id']] = $event['title'];
        }

        return $options;
    }
}
