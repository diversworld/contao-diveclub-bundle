<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Diversworld\ContaoDiveclubBundle\Service\TemplateService;


#[AsCallback(table: 'tl_dc_equipment', target: 'fields.size.options')]
class EquipmentSizeOptionsCallback
{
    private TemplateService $templateService;

    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    public function __invoke(): array
    {
        return $this->templateService->getSizes();
    }
}
