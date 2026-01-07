<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Date;
use Doctrine\DBAL\Connection;

#[AsCallback(table: 'tl_dc_course_event_schedule', target: 'list.label.label')]
class ScheduleLabelListener
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function __invoke(array $row, string $label, DataContainer $dc, array $args): array|string
    {
        if (is_array($args)) {
            if ($args[0]) {
                $args[0] = Date::parse(Config::get('datimFormat'), (int)$args[0]);
            }

            return $args;
        }

        $date = $row['planned_at'] ? Date::parse(Config::get('datimFormat'), (int)$row['planned_at']) : '-';
        return sprintf('%s — %s', $date, $label);
    }
}
