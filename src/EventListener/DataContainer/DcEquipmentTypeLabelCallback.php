<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Diversworld\ContaoDiveclubBundle\Service\TemplateService;


#[AsCallback(table: 'tl_dc_equipment_type', target: 'list.label.label')]
class DcEquipmentTypeLabelCallback
{
    private TemplateService $templateService;

    public function __construct(TemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    public function __invoke(array $row, string $label, DataContainer $dc): string
    {
        // Lade die Subtypen aus der Template-Datei
        $subTypes = $this->templateService->getTemplateOptions('dc_equipment_subTypes');

        // Ermittle den aktuellen Subtypen-Text
        $currentType = $row['title']; // Titel aus der Datenbankzeile
        $subTypeId = $row['subType']; // Subtype-ID aus der Datenbankzeile

        // Fallback: Subtype-ID verwenden, wenn keine Zuordnung gefunden wird
        $subTypeName = $subTypeId;

        if (isset($subTypes[$currentType][$subTypeId])) {
            $subTypeName = $subTypes[$currentType][$subTypeId];
        }

        return sprintf('%s: %s', $label, $subTypeName);
    }
}
