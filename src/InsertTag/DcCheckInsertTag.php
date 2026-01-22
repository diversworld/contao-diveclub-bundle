<?php

declare(strict_types=1);

/*
 * This file is part of ContaoDiveclubBundle.
 *
 * (c) Diversworld, Eckhard Becker 2025 <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\InsertTag;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\InsertTagResolverNestedResolvedInterface;
use Contao\Date;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckBookingModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckOrderModel;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsInsertTag('dc_check')]
class DcCheckInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult // Hauptmethode des Insert-Tag-Resolvers
    {
        $request = $this->requestStack->getCurrentRequest(); // Hole den aktuellen Request aus dem RequestStack

        if (null === $request) { // Falls kein Request vorhanden ist (z.B. CLI)
            return new InsertTagResult('', OutputType::text); // Gib ein leeres Ergebnis zurück
        }

        $orderId = $request->getSession()->get('last_tank_check_order'); // Hole die ID der letzten Bestellung aus der Session

        if (!$orderId) { // Falls keine Bestell-ID vorhanden ist
            return new InsertTagResult('', OutputType::text); // Gib ein leeres Ergebnis zurück
        }

        $order = DcCheckOrderModel::findByPk($orderId); // Lade das Bestell-Modell anhand der ID

        if (null === $order) { // Falls die Bestellung nicht gefunden wurde
            return new InsertTagResult('', OutputType::text); // Gib ein leeres Ergebnis zurück
        }

        $property = $insertTag->getParameters()->get(0); // Hole den ersten Parameter des Insert-Tags (das gewünschte Feld)

        if (!$property) { // Falls kein Feld angegeben wurde
            return new InsertTagResult('', OutputType::text); // Gib ein leeres Ergebnis zurück
        }

        $value = null; // Initialisiere den Wert

        // Check order properties first
        if (isset($order->$property) && $order->$property !== null) { // Prüfe zuerst, ob die Eigenschaft in der Bestellung existiert
            $value = $order->$property; // Übernehme den Wert aus der Bestellung
        } else {
            // Check booking (parent) properties
            $booking = DcCheckBookingModel::findByPk($order->pid); // Lade die zugehörige Buchung (Eltern-Datensatz)

            if (null !== $booking && isset($booking->$property) && $booking->$property !== null) { // Falls Buchung existiert und Eigenschaft dort vorhanden ist
                $value = $booking->$property; // Übernehme den Wert aus der Buchung
            }
        }

        if (null === $value) { // Falls kein Wert gefunden wurde
            return new InsertTagResult('', OutputType::text); // Gib ein leeres Ergebnis zurück
        }

        // Handle date fields
        if (in_array($property, ['tstamp', 'bookingDate'], true)) { // Falls es sich um ein Datumsfeld handelt
            $value = Date::parse(Config::get('datimFormat'), (int) $value); // Formatiere den Zeitstempel nach Contao-Standard
        }

        return new InsertTagResult((string) $value, OutputType::text); // Gib den finalen Wert als InsertTagResult zurück
    }
}
