<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;

class StudentExerciseLabelListener
{
    #[AsCallback(table: 'tl_dc_student_exercises', target: 'list.label.label')]
    public function __invoke(array $row, string $label, DataContainer $dc, ?array $args = null): array|string
    {
        $db = Database::getInstance();

        // Übungs- und Modulnamen über die Template-Tabellen holen
        if ($row['exercise_id'] > 0) {
            $objInfo = $db->prepare("
                SELECT e.title AS exTitle, m.title AS modTitle
                FROM tl_dc_course_exercises e
                JOIN tl_dc_course_modules m ON m.id = ?
                WHERE e.id = ?
            ")->execute($row['module_id'] ?: 0, $row['exercise_id']);
            $title = $objInfo->exTitle;
        } else {
            $objInfo = $db->prepare("
                SELECT title AS modTitle
                FROM tl_dc_course_modules
                WHERE id = ?
            ")->execute($row['module_id'] ?: 0);
            $title = 'Modul-Abschluss';
        }

        if ($objInfo->numRows < 1) {
            return $label;
        }

        $statusLabel = $GLOBALS['TL_LANG']['tl_dc_student_exercises']['itemStatus'][$row['status']] ?? $row['status'];
        $color = ($row['status'] === 'ok') ? '#2fb31b' : (($row['status'] === 'pending') ? '#ff8000' : '#ff0000');

        if (is_array($args)) {
            $args[0] = sprintf(
                '<span style="color:#999; width:150px; display:inline-block;">[%s]</span> <span style="width:250px; display:inline-block;"><strong>%s</strong></span>',
                $objInfo->modTitle,
                $title
            );
            $args[1] = sprintf('<span style="color:%s; font-weight:bold;">%s</span>', $color, $statusLabel);
            return $args;
        }

        return sprintf(
            '<span style="color:#999; width:150px; display:inline-block;">[%s]</span> <span style="width:250px; display:inline-block;"><strong>%s</strong></span> — <span style="color:%s; font-weight:bold;">%s</span>',
            $objInfo->modTitle,
            $title,
            $color,
            $statusLabel
        );
    }
}
