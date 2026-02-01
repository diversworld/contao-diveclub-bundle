<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Date;
use Doctrine\DBAL\Connection;

class CourseStudentLabelCallback
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[AsCallback(table: 'tl_dc_course_students', target: 'list.label.label')]
    public function __invoke(array $row, string $label, DataContainer $dc, ?array $args = null): array|string
    {
        // 1. Kurs-Titel über Doctrine DBAL abrufen
        $courseTitle = $this->connection->fetchOne(
            "SELECT title FROM tl_dc_dive_course WHERE id = ?",
            [$row['course_id']]
        ) ?: '-';

        // 2. Status übersetzen (aus den Sprachdateien)
        $statusLabel = $GLOBALS['TL_LANG']['tl_dc_course_students']['itemStatus'][$row['status']] ?? (string)$row['status'];

        // 3. Datum formatieren (registered_on)
        if (!empty($row['registered_on']) && is_numeric($row['registered_on'])) {
            $dateLabel = Date::parse(Config::get('dateFormat'), (int)$row['registered_on']);
        } else {
            $dateLabel = '-';
        }

        // 4. Bezahlt-Status (Checkbox) lesbar machen
        $payedLabel = !empty($row['payed']) ? 'ja' : 'nein';

        // Falls der Callback mit 4. Parameter (Array der Spaltenwerte) aufgerufen wird, befüllen
        // wir dieses Array wie bisher und geben es zurück (Kompatibilität zu showColumns=true).
        if (is_array($args)) {
            $args[0] = $courseTitle;      // course_id (als Titel angezeigt)
            $args[1] = $statusLabel;      // status
            $args[2] = $dateLabel;        // registered_on
            $args[3] = $payedLabel;       // payed

            return $args;
        }

        // Andernfalls (ältere Signatur, nur 3 Parameter), geben wir einen String zurück.
        return sprintf('%s — Status: %s (Angemeldet am: %s), Bezahlt: %s', $courseTitle, $statusLabel, $dateLabel, $payedLabel);
    }
}
