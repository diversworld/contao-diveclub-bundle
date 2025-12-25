<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Date;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_dc_course_students', target: 'list.label.label')]
class CourseStudentLabelCallback
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function __invoke(array $row, string $label, DataContainer $dc, array $args): array
    {
        // 1. Kurs-Titel über Doctrine DBAL abrufen
        $courseTitle = $this->connection->fetchOne(
            "SELECT title FROM tl_dc_dive_course WHERE id = ?",
            [$row['course_id']]
        );
        $args[0] = $courseTitle ?: '-';

        // 2. Status übersetzen (aus den Sprachdateien)
        $args[1] = $GLOBALS['TL_LANG']['tl_dc_course_students']['itemStatus'][$row['status']] ?? $row['status'];

        // 3. Datum formatieren (registered_on)
        if ($row['registered_on'] && is_numeric($row['registered_on'])) {
            $args[2] = Date::parse(Config::get('dateFormat'), (int)$row['registered_on']);
        } else {
            $args[2] = '-';
        }

        // 4. Bezahlt-Status (Checkbox) lesbar machen
        $args[3] = $row['payed'] ? 'ja' : 'nein';

        return $args;
    }
}
