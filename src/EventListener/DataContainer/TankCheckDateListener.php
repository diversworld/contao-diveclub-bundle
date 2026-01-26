<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use DateTime;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class TankCheckDateListener
{
    private Connection $db;
    private LoggerInterface $logger;

    public function __construct(Connection $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    #[AsCallback(table: 'tl_dc_tanks', target: 'fields.checkId.save')]
    public function __invoke($varValue, DataContainer $dc)
    {
        $this->logger->info(
            'Varvalue: ' . $varValue,
            ['contao' => new \Contao\CoreBundle\Monolog\ContaoContext(__METHOD__, \Contao\CoreBundle\Monolog\ContaoContext::GENERAL)]
        );

        if ($varValue) {
            $startDate = $this->db->fetchOne("SELECT startDate FROM tl_calendar_events WHERE id = ?", [$varValue]);

            if ($startDate) {
                $this->logger->info(
                    'StartDate: ' . $startDate,
                    ['contao' => new \Contao\CoreBundle\Monolog\ContaoContext(__METHOD__, \Contao\CoreBundle\Monolog\ContaoContext::GENERAL)]
                );

                $lastCheckDate = new DateTime('@' . $startDate);
                $lastCheckDate->modify('+2 years');

                $nextCheckDate = $lastCheckDate->getTimestamp();

                $this->db->executeStatement(
                    "UPDATE tl_dc_tanks SET lastCheckDate = ?, nextCheckDate = ? WHERE id = ?",
                    [$startDate, $nextCheckDate, $dc->id]
                );
            }
        }

        return $varValue;
    }
}
