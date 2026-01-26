<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Date;

#[AsCallback(table: 'tl_dc_regulator_control', target: 'list.label.label')]
class RegulatorControlLabelListener
{
    /**
     * @param array $row
     * @param string $label
     * @param DataContainer|null $dc
     * @param array|null $args
     * @return array|string
     */
    public function __invoke(array $row, string $label, ?DataContainer $dc = null, ?array $args = null): array|string
    {
        if ($args) {
            $args[1] = $row['actualCheckDate'] ? Date::parse(Config::get('dateFormat'), (int) $row['actualCheckDate']) : '-';
            // Index 2 to 9 are: midPressurePre30, midPressurePre200, inhalePressurePre, exhalePressurePre, midPressurePost30, midPressurePost200, inhalePressurePost, exhalePressurePost
            // We can just keep them as they are or add units if needed.
            // But they are already in the format string.
        }

        return $args ?? $label;
    }
}
