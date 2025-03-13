<?php

namespace Diversworld\ContaoDiveclubBundle\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ModuleConfigService
{
    private string $moduleTitle;
    private string $moduleDescription;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->moduleTitle = $parameterBag->get('diversworld_contao_diveclub.module_title');
        $this->moduleDescription = $parameterBag->get('diversworld_contao_diveclub.module_description');
    }

    public function getModuleTitle(): string
    {
        return $this->moduleTitle;
    }

    public function getModuleDescription(): string
    {
        return $this->moduleDescription;
    }
}
