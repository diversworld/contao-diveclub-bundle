<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use function Symfony\Component\Translation\t;

#[AsCallback(table: 'tl_dc_equipment', target: 'list.label.label')]
class EquipmentLabelCallback
{
    private DcaTemplateHelper $templateHelper;

    public function __construct(DcaTemplateHelper $templateHelper)
    {
        $this->templateHelper = $templateHelper;
    }

    public function __invoke(array $row, string $label, DataContainer $dc, array $labels): array
    {
        // Typ und Untertyp-Name abrufen
        $types = $this->templateHelper->getEquipmentFlatTypes();
        $labels[0] = $types[$row['type']] ?? '-';
        $subTypes = $this->templateHelper->getSubTypes($row['type']);
        $labels[1] = $subTypes[$row['subType']] ?? '-';

        // Hersteller-Name abrufen
        $manufacturers = $this->templateHelper->getManufacturers();
        $labels[3] = $manufacturers[$row['manufacturer']] ?? '-';

        // Größen-Name abrufen
        $sizes = $this->templateHelper->getSizes();
        $labels[5] = $sizes[$row['size']] ?? '-';
        $labels[6] = number_format((float)$row['rentalFee'], 2, '.', ',') . ' €'; // z. B. "123.45 €"
        $labels[7] = $GLOBALS['TL_LANG']['tl_dc_equipment']['itemStatus'][$row['status']] ?? '-';

        return $labels;
    }
}
