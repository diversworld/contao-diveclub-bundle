<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\StringUtil;
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
    public function getInstructorOptions(?DataContainer $dc = null): array
    {
        $options = [];
        try {
            $instructorGroups = $this->getConfiguredInstructorGroups();
            $fallbackGroupId = $this->getInstructorenFallbackGroupId();

            if ($instructorGroups === [] && $fallbackGroupId > 0) {
                $instructorGroups = [$fallbackGroupId];
            }

            if ($instructorGroups === []) {
                return [];
            }

            $members = $this->db->fetchAllAssociative("SELECT id, firstname, lastname, groups FROM tl_member ORDER BY lastname, firstname");

            foreach ($members as $member) {
                // Contao speichert Gruppen serialisiert; erst nach dem Deserialisieren
                // vergleichen, damit zweistellige Gruppen-IDs nicht versehentlich passen.
                $memberGroups = array_filter(array_map('intval', StringUtil::deserialize($member['groups'], true)));

                if (array_intersect($instructorGroups, $memberGroups) !== []) {
                    $options[$member['id']] = trim($member['firstname'] . ' ' . $member['lastname']);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('DB Error in getInstructorOptions: ' . $e->getMessage());
        }

        return $options;
    }

    private function getConfiguredInstructorGroups(): array
    {
        $serializedGroups = (string)($this->db->fetchOne(
            "SELECT instructor_groups FROM tl_dc_config WHERE published='1' OR published=1 LIMIT 1",
        ) ?: '');

        return array_values(array_unique(array_filter(
            array_map('intval', StringUtil::deserialize($serializedGroups, true)),
        )));
    }

    private function getInstructorenFallbackGroupId(): int
    {
        return (int)$this->db->fetchOne(
            "SELECT id FROM tl_member_group WHERE LOWER(name) = 'instruktoren' ORDER BY id LIMIT 1",
        );
    }
}
