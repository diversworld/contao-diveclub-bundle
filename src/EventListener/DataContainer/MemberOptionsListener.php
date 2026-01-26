<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class MemberOptionsListener
{
    private Connection $db;
    private LoggerInterface $logger;

    public function __construct(Connection $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    #[AsCallback(table: 'tl_dc_tanks', target: 'fields.owner.options')]
    #[AsCallback(table: 'tl_dc_check_booking', target: 'fields.memberId.options')]
    #[AsCallback(table: 'tl_dc_students', target: 'fields.memberId.options')]
    #[AsCallback(table: 'tl_dc_reservation', target: 'fields.member_id.options')]
    #[AsCallback(table: 'tl_dc_reservation', target: 'fields.reservedFor.options')]
    public function __invoke(?DataContainer $dc = null): array
    {
        $options = [];
        try {
            $members = $this->db->fetchAllAssociative("SELECT id, firstname, lastname FROM tl_member ORDER BY lastname, firstname");

            foreach ($members as $member) {
                $options[$member['id']] = $member['firstname'] . ' ' . $member['lastname'];
            }
        } catch (\Exception $e) {
            $this->logger->error('DB Error: ' . $e->getMessage());
        }

        return $options;
    }
}
