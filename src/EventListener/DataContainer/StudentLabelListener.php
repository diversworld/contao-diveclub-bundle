<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Date;

#[AsCallback(table: 'tl_dc_students', target: 'list.label.label')]
class StudentLabelListener
{
    public function __invoke(array $row, string $label, DataContainer $dc, ?array $args = null): array|string
    {
        if (null !== $args) {
            if ($args[2]) {
                $args[2] = Date::parse(Config::get('dateFormat'), (int)$args[2]);
            }

            return $args;
        }

        $dob = $row['dateOfBirth'] ? Date::parse(Config::get('dateFormat'), (int)$row['dateOfBirth']) : '-';
        return sprintf('%s, %s (%s)', $row['lastname'], $row['firstname'], $dob);
    }
}
