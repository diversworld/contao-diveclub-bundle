<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;

#[AsCallback(table: 'tl_dc_equipment', target: 'fields.type.options')]
class EquipmentTypeOptionsCallback
{
    private DcaTemplateHelper $templateHelper;

    public function __construct(DcaTemplateHelper $templateHelper)
    {
        $this->templateHelper = $templateHelper;
    }

    public function __invoke(): array
    {
        return $this->templateHelper->getEquipmentFlatTypes();
    }
}
