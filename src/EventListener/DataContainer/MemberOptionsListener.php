<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class MemberOptionsListener
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    #[AsCallback(table: 'tl_dc_tanks', target: 'fields.owner.options')]
    #[AsCallback(table: 'tl_dc_check_booking', target: 'fields.memberId.options')]
    #[AsCallback(table: 'tl_dc_students', target: 'fields.memberId.options')]
    #[AsCallback(table: 'tl_dc_reservation', target: 'fields.member_id.options')]
    #[AsCallback(table: 'tl_dc_reservation', target: 'fields.reservedFor.options')]
    public function __invoke(): array
    {
        $options = [];
        $members = $this->db->fetchAllAssociative("SELECT id, CONCAT(firstname, ' ', lastname) as name FROM tl_member ORDER BY lastname, firstname");

        foreach ($members as $member) {
            $options[$member['id']] = $member['name'];
        }

        return $options;
    }
}
