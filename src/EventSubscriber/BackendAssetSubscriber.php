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

class BackendAssetSubscriber implements EventSubscriberInterface // Subscriber zum Laden von Assets im Backend
{
    private string $moduleTitle; // Variable für den Modultitel
    private string $moduleDescription; // Variable für die Modulbeschreibung

    public function __construct(private readonly ScopeMatcher $scopeMatcher, ParameterBagInterface $parameterBag) // Konstruktor mit Dependency Injection
    {
        $this->moduleTitle = $parameterBag->get('diversworld_contao_diveclub.module_title'); // Lade Modultitel aus den Parametern
        $this->moduleDescription = $parameterBag->get('diversworld_contao_diveclub.module_description'); // Lade Beschreibung aus den Parametern
    }

    public static function getSubscribedEvents(): array // Definiert die abonnierten Events
    {
        return [KernelEvents::REQUEST => 'onKernelRequest']; // Reagiere auf das REQUEST Event des Kernels
    }

    public function onKernelRequest(RequestEvent $e): void // Methode die beim Request Event ausgeführt wird
    {
        $request = $e->getRequest(); // Hole den aktuellen Request aus dem Event

        if ($this->scopeMatcher->isBackendRequest($request)) { // Prüfe ob es sich um einen Backend-Request handelt
            $GLOBALS['TL_CSS'][] = 'bundles/diversworldcontaodiveclub/css/backend.css'; // Füge das bundle-eigene CSS zum Backend hinzu

            // Setze den Sprachschlüssel nur, wenn er nicht korrekt gesetzt ist
            if (
                !isset($GLOBALS['TL_LANG']['MOD']['diveclub']) ||
                $GLOBALS['TL_LANG']['MOD']['diveclub'][0] !== $this->moduleTitle ||
                $GLOBALS['TL_LANG']['MOD']['diveclub'][1] !== $this->moduleDescription
            ) { // Falls die Modulübersetzung fehlt oder veraltet ist
                $GLOBALS['TL_LANG']['MOD']['diveclub'] = [ // Setze die Übersetzung im globalen Sprach-Array
                    $this->moduleTitle, // Titel aus Bundle-Konfiguration
                    $this->moduleDescription, // Beschreibung aus Bundle-Konfiguration
                ];
            }
        }
    }
}
