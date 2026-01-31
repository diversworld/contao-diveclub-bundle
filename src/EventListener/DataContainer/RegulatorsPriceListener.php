<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

#[AsCallback(table: 'tl_dc_regulators', target: 'fields.rentalFee.save')]
class RegulatorsPriceListener
{
    public function __invoke($value): float
    {
        if (empty($value)) {
            return 0.00;
        }

        // Entferne eventuell angefügte Währungszeichen und whitespace
        $value = str_replace(['€', ' '], '', (string) $value);

        // Stelle sicher, dass es ein gültiger Dezimalwert ist
        return round((float) $value, 2);
    }
}
