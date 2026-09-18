<?php

declare(strict_types=1);

/*
 * This file is part of ContaoDiveclubBundle.
 *
 * (c) DiversWorld 2024 <eckhard@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

#[AsCallback(table: 'tl_dc_check_booking', target: 'edit.buttons', priority: 100)]
class DcCheckBooking // DataContainer-Klasse für die Verwaltung von Buchungs-Callbacks
{
    private ContaoFramework $framework; // Variable für das Contao Framework
    private RouterInterface $router; // Variable für den Symfony Router

    public function __construct(ContaoFramework $framework, RouterInterface $router) // Konstruktor mit Dependency Injection
    {
        $this->framework = $framework; // Zuweisung des Frameworks
        $this->router = $router; // Zuweisung des Routers
    }

    public function __invoke(array $arrButtons, DataContainer $dc): array // Invoke-Methode zur Manipulation der Backend-Buttons
    {
        $inputAdapter = $this->framework->getAdapter(Input::class); // Hole den Adapter für Input-Operationen
        $systemAdapter = $this->framework->getAdapter(System::class); // Hole den Adapter für System-Operationen

        $systemAdapter->loadLanguageFile('tl_dc_check_booking'); // Lade die Sprachdatei für die Buchungsverwaltung

        if ('edit' === $inputAdapter->get('act') && $inputAdapter->post('pdfButton')) { // Falls im Bearbeitungsmodus und der PDF-Button geklickt wurde
            $url = $this->router->generate('dc_check_order_pdf', ['id' => $dc->id]); // Generiere die URL für den PDF-Export
            (new RedirectResponse($url))->send(); // Führe eine Weiterleitung zur PDF-Generierung aus
            exit; // Beende die Skriptausführung nach der Weiterleitung
        }

        if ('edit' === $inputAdapter->get('act')) { // Falls im Bearbeitungsmodus (Initialanzeige)
            $arrButtons['customButton'] = '<button type="submit" name="pdfButton" id="pdfButton" class="tl_submit pdfButton" accesskey="p">' . $GLOBALS['TL_LANG']['tl_dc_check_booking']['pdfButton'] . '</button>'; // Füge den benutzerdefinierten PDF-Button zum Button-Array hinzu
        }

        return $arrButtons; // Gib das aktualisierte Button-Array zurück
    }
}
