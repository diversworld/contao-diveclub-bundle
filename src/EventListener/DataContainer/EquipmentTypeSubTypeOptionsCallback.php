<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Diversworld\ContaoDiveclubBundle\Service\TemplateService;

#[AsCallback(table: 'tl_dc_equipment_type', target: 'fields.subType.options')]
class EquipmentTypeSubTypeOptionsCallback
{
    private DcaTemplateHelper $templateHelper;

    public function __construct(DcaTemplateHelper $templateHelper)
    {
        $this->templateHelper = $templateHelper;
    }

    public function __invoke(DataContainer $dc = null): array
    {
        return $this->templateHelper->getSubTypes($dc);
    }
}
