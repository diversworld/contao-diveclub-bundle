<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;


#[AsCallback(table: 'tl_dc_reservation_items', target: 'fields.sub_type.options')]
class ReservationItemsSubTypeOptionsCallback
{
    private DcaTemplateHelper $templateHelper;

    public function __construct(DcaTemplateHelper $templateHelper)
    {
        $this->templateHelper = $templateHelper;
    }

    public function __invoke(DataContainer $dc): array
    {
        // Wenn kein activeRecord oder der Typ fehlt, Rückgabe eines leeren Arrays
        if (!$dc->activeRecord || !$dc->activeRecord->types) {
            return [];
        }

        return $this->templateHelper->getSubTypes((int) $dc->activeRecord->types);
    }
}
