<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;

#[AsCallback(table: 'tl_dc_equipment_type', target: 'list.label.label_callback')]
class EquipmentTypeLabelCallback
{
    private DcaTemplateHelper $templateHelper;

    public function __construct(DcaTemplateHelper $templateHelper)
    {
        $this->templateHelper = $templateHelper;
    }

    public function __invoke(array $row, string $label, DataContainer $dc): string
    {
        $args[2] = number_format((float) $row['rentalFee'], 2, ',', '.') . ' €'; // z. B. "1.234,56 €"

        // Lade die Subtypen aus der Template-Datei
        $types = $this->templateHelper->getTemplateOptions('dc_equipment_types');
        $subTypes = $this->templateHelper->getTemplateOptions('dc_equipment_subTypes');

        // Fallback: Subtype-ID verwenden, wenn keine Zuordnung gefunden wird
        $subTypeName = $row['subType'];
        $typeName = $types[$row['title']];
        if (isset($subTypes[$row['title']][$row['subType']])) {
            $subTypeName = $subTypes[$row['title']][$row['subType']];
        }

        return sprintf('%s: %s - %s', $typeName, $subTypeName, $args[2]);
    }
}
