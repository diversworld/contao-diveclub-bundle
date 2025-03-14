<?php


declare(strict_types=1);

/*
 * This file is part of Diveclub App.
 *
 * (c) Eckhard Becker 2025 <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\EventSubscriber;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Markocupic\ResourceBookingBundle\Config\RbbConfig;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class BackendAssetSubscriber implements EventSubscriberInterface
{
    private string $moduleTitle;
    private string $moduleDescription;

    public function __construct(private readonly ScopeMatcher $scopeMatcher, ParameterBagInterface $parameterBag)
    {
        $this->moduleTitle = $parameterBag->get('diversworld_contao_diveclub.module_title');
        $this->moduleDescription = $parameterBag->get('diversworld_contao_diveclub.module_description');
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    public function onKernelRequest(RequestEvent $e): void
    {
        $request = $e->getRequest();

        if ($this->scopeMatcher->isBackendRequest($request)) {
            $GLOBALS['TL_CSS'][] = 'bundles/diversworldcontaodiveclub/css/backend.css';

            // Setze den Sprachschlüssel nur, wenn er nicht korrekt gesetzt ist
            if (
                !isset($GLOBALS['TL_LANG']['MOD']['diveclub']) ||
                $GLOBALS['TL_LANG']['MOD']['diveclub'][0] !== $this->moduleTitle ||
                $GLOBALS['TL_LANG']['MOD']['diveclub'][1] !== $this->moduleDescription
            ) {
                $GLOBALS['TL_LANG']['MOD']['diveclub'] = [
                    $this->moduleTitle,
                    $this->moduleDescription,
                ];
            }
        }
    }
}
