<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;

class RegulatorsAliasListener extends AbstractAliasListener
{
    protected function getTable(): string
    {
        return 'tl_dc_regulators';
    }

    #[AsCallback(table: 'tl_dc_regulators', target: 'fields.alias.save')]
    public function __invoke(mixed $varValue, DataContainer $dc): mixed
    {
        return $this->generateAlias($varValue, $dc);
    }
}
