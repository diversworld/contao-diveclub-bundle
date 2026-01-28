<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;

class CourseModuleOptionsListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[AsCallback(table: 'tl_dc_course_modules', target: 'fields.preModule.options')]
    public function __invoke(DataContainer $dc): array
    {
        $options = [];

        // Holen aller Kurse und ihrer Module
        // Wir schließen das aktuelle Modul selbst aus ($dc->id), um Zirkelbezüge zu vermeiden
        $rows = $this->connection->fetchAllAssociative("
            SELECT m.id, m.title AS moduleTitle, c.title AS courseTitle
            FROM tl_dc_course_modules m
            LEFT JOIN tl_dc_dive_course c ON m.pid = c.id
            WHERE m.id != ?
            ORDER BY c.title, m.title
        ", [(int) ($dc->id ?: 0)]);

        foreach ($rows as $row) {
            $options[$row['courseTitle']][$row['id']] = $row['moduleTitle'];
        }

        return $options;
    }
}
