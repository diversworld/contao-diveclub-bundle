<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;
use Contao\StringUtil;

class InstructorOptionsListener
{
    #[AsCallback(table: 'tl_dc_course_event', target: 'fields.instructor.options')]
    #[AsCallback(table: 'tl_dc_student_exercises', target: 'fields.instructor.options')]
    public function onGetInstructors(?DataContainer $dc = null): array
    {
        $db = Database::getInstance();
        $configResult = $db->prepare("SELECT instructor_groups FROM tl_dc_config WHERE published='1' LIMIT 1")->execute();

        $instructorGroups = [];
        if ($configResult->numRows > 0) {
            $instructorGroups = StringUtil::deserialize($configResult->instructor_groups, true);
        }

        if (empty($instructorGroups)) {
            return [];
        }

        $instructors = [];
        // Wir suchen Mitglieder (tl_member), die in mindestens einer der Instructor-Gruppen sind
        $memberResult = $db->execute("SELECT id, firstname, lastname, `groups` FROM tl_member ORDER BY lastname, firstname");

        while ($memberResult->next()) {
            $groups = StringUtil::deserialize($memberResult->groups, true);
            foreach ($instructorGroups as $groupId) {
                if (in_array((string)$groupId, $groups, true)) {
                    $instructors[$memberResult->id] = $memberResult->firstname . ' ' . $memberResult->lastname;
                    break;
                }
            }
        }

        return $instructors;
    }
}
