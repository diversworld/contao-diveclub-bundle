<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Doctrine\DBAL\Connection;

class CourseExerciseOptionsListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[AsCallback(table: 'tl_dc_course_exercises', target: 'fields.prerequisites.options')]
    public function __invoke(): array
    {
        $options = [];

        // Holen aller Module und ihrer Übungen
        $rows = $this->connection->fetchAllAssociative("
            SELECT e.id, e.title AS exerciseTitle, m.title AS moduleTitle
            FROM tl_dc_course_exercises e
            LEFT JOIN tl_dc_course_modules m ON e.pid = m.id
            ORDER BY m.title, e.sorting
        ");

        foreach ($rows as $row) {
            $options[$row['moduleTitle']][$row['id']] = $row['exerciseTitle'];
        }

        return $options;
    }
}
