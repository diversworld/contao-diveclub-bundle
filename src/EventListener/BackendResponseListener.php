<?php

declare(strict_types=1);

/*
 * This file is part of Backend Customizer for Contao Open Source CMS.
 *
 * (c) bwein.net
 *
 * @license MIT
 */

namespace Diversworld\ContaoDiveclubBundle\EventListener;


use Contao\CoreBundle\Routing\ScopeMatcher;
use Diversworld\ContaoDiveclubBundle\ParameterBag\BackendParameterBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class BackendResponseListener
{
    public function __invoke(ResponseEvent $event): void
    {

    }
}
