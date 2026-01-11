<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Database;
use Contao\DataContainer;

#[AsCallback(table: 'tl_dc_check_order', target: 'list.label.label')]
class OrderLabelListener
{
    public function __invoke(array $row, string $label, DataContainer $dc, array $args = null): array|string
    {
        if (null === $args) {
            return sprintf(
                '%s (%sL) - %s € [%s]',
                $row['serialNumber'],
                $row['size'],
                number_format((float)$row['totalPrice'], 2, ',', '.'),
                $row['status']
            );
        }

        $args[0] = sprintf(
            '%s (%sL)',
            $row['serialNumber'],
            $row['size']
        );
        $args[1] = number_format((float)$row['totalPrice'], 2, ',', '.') . ' €';
        $args[2] = $GLOBALS['TL_LANG']['tl_dc_check_order'][$row['status']];

        return $args;
    }
}
