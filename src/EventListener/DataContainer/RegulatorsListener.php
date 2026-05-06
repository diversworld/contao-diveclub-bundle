<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Contao\Date;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Doctrine\DBAL\Connection;
use Exception;

class RegulatorsListener
{
    use AliasHandlerTrait;

    public function __construct(
        private readonly Connection        $db,
        private readonly DcaTemplateHelper $templateHelper,
        private readonly Slug              $slug
    )
    {
    }

    #[AsCallback(table: 'tl_dc_regulators', target: 'fields.alias.save')]
    public function onAliasSave(mixed $varValue, DataContainer $dc): mixed
    {
        return $this->generateAliasWithValidation($this->db, $this->slug, $varValue, $dc, 'tl_dc_regulators');
    }

    #[AsCallback(table: 'tl_dc_regulators', target: 'list.label.label')]
    public function onLabelCallback(array $row, string $label, ?DataContainer $dc = null, ?array $args = null): array|string
    {
        if (null === $args) {
            return $label;
        }

        $manufacturerId = (int)$row['manufacturer'];
        $manufacturers = $this->templateHelper->getManufacturers();
        $args[1] = $manufacturers[$manufacturerId] ?? '-';

        $models1st = $this->templateHelper->getRegModels1st($manufacturerId, $dc);
        $models2nd = $this->templateHelper->getRegModels2nd($manufacturerId, $dc);

        $args[2] = $models1st[(int)$row['regModel1st']] ?? '-';
        $args[3] = $models2nd[(int)$row['regModel2ndPri']] ?? '-';
        $args[4] = $models2nd[(int)$row['regModel2ndSec']] ?? '-';

        $latestRevision = $this->db->fetchOne(
            "SELECT actualCheckDate FROM tl_dc_regulator_control WHERE pid = ? ORDER BY actualCheckDate DESC LIMIT 1",
            [$row['id']]
        );

        $args[6] = $latestRevision ? Date::parse(Config::get('dateFormat'), (int)$latestRevision) : '-';

        return $args;
    }

    #[AsCallback(table: 'tl_dc_regulators', target: 'fields.regModel1st.options')]
    public function onRegModel1stOptions(DataContainer $dc): array
    {
        return $this->templateHelper->getRegModels1st(null, $dc);
    }

    #[AsCallback(table: 'tl_dc_regulators', target: 'fields.regModel2ndPri.options')]
    public function onRegModel2ndPriOptions(DataContainer $dc): array
    {
        return $this->templateHelper->getRegModels2nd(null, $dc);
    }

    #[AsCallback(table: 'tl_dc_regulators', target: 'fields.regModel2ndSec.options')]
    public function onRegModel2ndSecOptions(DataContainer $dc): array
    {
        return $this->templateHelper->getRegModels2nd(null, $dc);
    }

    #[AsCallback(table: 'tl_dc_regulators', target: 'fields.rentalFee.save')]
    public function onRentalFeeSave($value): float
    {
        if (empty($value)) {
            return 0.00;
        }

        $value = str_replace(['€', ' '], '', (string)$value);
        return round((float)$value, 2);
    }

    #[AsCallback(table: 'tl_dc_regulator_control', target: 'list.label.label')]
    public function onControlLabelCallback(array $row, string $label, ?DataContainer $dc = null, ?array $args = null): array|string
    {
        if ($args) {
            $args[1] = $row['actualCheckDate'] ? Date::parse(Config::get('dateFormat'), (int)$row['actualCheckDate']) : '-';
        }

        return $args ?? $label;
    }
}
