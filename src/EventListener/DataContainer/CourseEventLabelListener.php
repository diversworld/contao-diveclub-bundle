<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Date;
use Doctrine\DBAL\Connection;

class CourseEventLabelListener
{
    #[AsCallback(table: 'tl_dc_course_event', target: 'list.label.label')]
    public function __invoke(array $row, string $label, DataContainer $dc, ?array $args = null): array|string
    {
        if (null !== $args) {
            if ($args[1]) {
                $args[1] = Date::parse(Config::get('datimFormat'), (int)$args[1]);
            } else {
                $args[1] = 'kein Datum';
            }

            return $args;
        }

        $date = $row['dateStart'] ? Date::parse(Config::get('datimFormat'), (int)$row['dateStart']) : 'kein Datum';
        return sprintf('%s <span style="color:#999;">(%s)</span>', $row['title'], $date);
    }
}
