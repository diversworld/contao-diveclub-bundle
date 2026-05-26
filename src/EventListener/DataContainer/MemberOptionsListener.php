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

    #[AsCallback(table: 'tl_dc_course_event', target: 'fields.instructor.options')]
    #[AsCallback(table: 'tl_dc_course_event_schedule', target: 'fields.instructor.options')]
    #[AsCallback(table: 'tl_dc_dive_course', target: 'fields.instructor.options')]
    #[AsCallback(table: 'tl_dc_event_schedule_exercises', target: 'fields.instructor.options')]
    #[AsCallback(table: 'tl_dc_student_exercises', target: 'fields.instructor.options')]
    #[AsCallback(table: 'tl_dc_students', target: 'fields.instructor_groups.options')]
    public function getInstructorOptions(?DataContainer $dc = null): array
    {
        $options = [];
        try {
            $instructorGroups = [];
            $config = $this->db->fetchAssociative("SELECT instructor_groups FROM tl_dc_config WHERE published='1' OR published=1 LIMIT 1");

            if ($config && !empty($config['instructor_groups'])) {
                $instructorGroups = array_filter((array)unserialize($config['instructor_groups']));
            }

            $query = "SELECT id, firstname, lastname FROM tl_member";
            $params = [];

            if (!empty($instructorGroups)) {
                $query .= " WHERE (";
                $groupConditions = [];
                foreach ($instructorGroups as $groupId) {
                    $groupConditions[] = "groups LIKE ?";
                    $params[] = '%"' . $groupId . '"%';
                }
                $query .= implode(' OR ', $groupConditions) . ")";
            }

            $query .= " ORDER BY lastname, firstname";
            $members = $this->db->fetchAllAssociative($query, $params);

            foreach ($members as $member) {
                $options[$member['id']] = $member['firstname'] . ' ' . $member['lastname'];
            }
        } catch (\Exception $e) {
            $this->logger->error('DB Error in getInstructorOptions: ' . $e->getMessage());
        }

        return $options;
    }
}
