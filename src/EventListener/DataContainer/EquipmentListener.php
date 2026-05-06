<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Doctrine\DBAL\Connection;
use Exception;

class EquipmentListener
{
    use AliasHandlerTrait;

    public function __construct(
        private readonly Connection        $db,
        private readonly DcaTemplateHelper $templateHelper,
        private readonly Slug              $slug
    )
    {
    }

    #[AsCallback(table: 'tl_dc_equipment', target: 'fields.alias.save')]
    public function onAliasSave(mixed $varValue, DataContainer $dc): mixed
    {
        return $this->generateAliasWithValidation($this->db, $this->slug, $varValue, $dc, 'tl_dc_equipment');
    }

    #[AsCallback(table: 'tl_dc_equipment', target: 'list.label.label')]
    public function onLabelCallback(array $row, string $label, DataContainer $dc, ?array $args = null): array|string
    {
        if (null === $args) {
            $types = $this->templateHelper->getEquipmentFlatTypes();
            $typeName = $types[$row['type']] ?? '-';
            return sprintf('%s — %s', $typeName, $row['title']);
        }

        $labels = $args;
        $types = $this->templateHelper->getEquipmentFlatTypes();
        $labels[0] = $types[$row['type']] ?? '-';
        $subTypes = $this->templateHelper->getSubTypes((int)$row['type']);
        $labels[1] = $subTypes[$row['subType']] ?? '-';

        $manufacturers = $this->templateHelper->getManufacturers();
        $labels[3] = $manufacturers[$row['manufacturer']] ?? '-';

        $sizes = $this->templateHelper->getSizes();
        $labels[5] = $sizes[$row['size']] ?? '-';
        $labels[6] = number_format((float)$row['rentalFee'], 2, '.', ',') . ' €';
        $labels[7] = $GLOBALS['TL_LANG']['tl_dc_equipment']['itemStatus'][$row['status']] ?? '-';

        return $labels;
    }

    #[AsCallback(table: 'tl_dc_equipment', target: 'fields.type.options')]
    public function onTypeOptions(): array
    {
        return $this->templateHelper->getEquipmentFlatTypes();
    }

    #[AsCallback(table: 'tl_dc_equipment', target: 'fields.subType.options')]
    public function onSubTypeOptions(DataContainer $dc): array
    {
        if (!$dc->activeRecord) {
            return [];
        }

        return $this->templateHelper->getSubTypes((int)$dc->activeRecord->type);
    }

    #[AsCallback(table: 'tl_dc_equipment', target: 'fields.manufacturer.options')]
    public function onManufacturerOptions(): array
    {
        return $this->templateHelper->getManufacturers();
    }

    #[AsCallback(table: 'tl_dc_equipment', target: 'fields.size.options')]
    public function onSizeOptions(): array
    {
        return $this->templateHelper->getSizes();
    }

    #[AsCallback(table: 'tl_dc_equipment_type', target: 'fields.title.options')]
    public function onTypeTitleOptions(): array
    {
        return $this->templateHelper->getEquipmentFlatTypes();
    }
}
