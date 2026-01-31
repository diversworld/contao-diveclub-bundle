<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Diversworld\ContaoDiveclubBundle\Helper\DcaTemplateHelper;

class RegulatorsOptionsCallback
{
    public function __construct(
        private readonly DcaTemplateHelper $templateHelper
    )
    {
    }

    #[AsCallback(table: 'tl_dc_regulators', target: 'fields.regModel1st.options')]
    public function getRegModels1st(DataContainer $dc): array
    {
        return $this->templateHelper->getRegModels1st(null, $dc);
    }

    #[AsCallback(table: 'tl_dc_regulators', target: 'fields.regModel2ndPri.options')]
    public function getRegModels2ndPri(DataContainer $dc): array
    {
        return $this->templateHelper->getRegModels2nd(null, $dc);
    }

    #[AsCallback(table: 'tl_dc_regulators', target: 'fields.regModel2ndSec.options')]
    public function getRegModels2ndSec(DataContainer $dc): array
    {
        return $this->templateHelper->getRegModels2nd(null, $dc);
    }
}
