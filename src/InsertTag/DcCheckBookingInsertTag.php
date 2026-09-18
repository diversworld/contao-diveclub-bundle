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
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\OutputType;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\InsertTagResolverNestedResolvedInterface;
use Contao\Date;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckBookingModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckOrderModel;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsInsertTag('booking')]
class DcCheckBookingInsertTag implements InsertTagResolverNestedResolvedInterface
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

        // Try to get booking ID from request attributes (for the PDF controller)
        $bookingId = $request->attributes->get('id'); // Versuche die Buchungs-ID aus den Request-Attributen zu lesen (wichtig für PDF-Export)

        // Fallback: Try to get order ID from session and find its booking
        if (!$bookingId) { // Falls keine Buchungs-ID gefunden wurde
            $orderId = $request->getSession()->get('last_tank_check_order'); // Versuche die ID der letzten Bestellung aus der Session zu holen
            if ($orderId) { // Falls eine Bestell-ID vorhanden ist
                $order = DcCheckOrderModel::findByPk($orderId); // Lade das Bestell-Modell
                if ($order) { // Falls die Bestellung existiert
                    $bookingId = $order->pid; // Nimm die PID (Parent ID) der Bestellung als Buchungs-ID
                }
            }
        }

        if (!$bookingId) { // Falls immer noch keine Buchungs-ID vorhanden ist
            return new InsertTagResult('', OutputType::text); // Gib ein leeres Ergebnis zurück
        }

        $booking = DcCheckBookingModel::findByPk($bookingId); // Lade das Buchungs-Modell anhand der ID

        if (null === $booking) { // Falls die Buchung nicht gefunden wurde
            return new InsertTagResult('', OutputType::text); // Gib ein leeres Ergebnis zurück
        }

        $property = $insertTag->getParameters()->get(0); // Hole den ersten Parameter des Insert-Tags (das gewünschte Feld)

        if (!$property) { // Falls kein Feld angegeben wurde
            return new InsertTagResult('', OutputType::text); // Gib ein leeres Ergebnis zurück
        }

        $value = $booking->$property; // Lies den Wert der Eigenschaft aus dem Buchungs-Modell

        if (null === $value) { // Falls der Wert in der Datenbank NULL ist
            return new InsertTagResult('', OutputType::text); // Gib ein leeres Ergebnis zurück
        }

        // Handle price fields
        if ($property === 'totalPrice') { // Falls es sich um das Preisfeld handelt
            $value = number_format((float) $value, 2, ',', '.') . ' €'; // Formatiere den Wert als Währung (Euro)
        }

        if (in_array($property, ['paid', 'status'], true)) { // Falls Sprachdateien für bestimmte Felder benötigt werden
            Controller::loadLanguageFile('tl_dc_check_booking'); // Lade die Sprachdatei der Buchungsverwaltung
        }

        if ($property === 'paid') { // Falls das Feld "bezahlt" abgefragt wird
            $value = $GLOBALS['TL_LANG']['tl_dc_check_booking']['paid_reference'][$value ? '1' : '0'] ?? ($value ? 'Ja' : 'Nein'); // Übersetze den Status (1/0) in Text (Ja/Nein)
        }

        // Handle status field
        if ($property === 'status') { // Falls das Feld "status" abgefragt wird
            $value = $GLOBALS['TL_LANG']['tl_dc_check_booking']['status_reference'][$value] ?? $value; // Übersetze den Status-Code in den entsprechenden Text aus der Sprachdatei
        }

        // Handle date fields
        if (in_array($property, ['tstamp', 'bookingDate'], true)) { // Falls es sich um ein Datumsfeld handelt
            $value = Date::parse(Config::get('datimFormat'), (int) $value); // Formatiere den Zeitstempel nach den Contao-Systemeinstellungen
        }

        return new InsertTagResult((string) $value, OutputType::text); // Gib den finalen Wert als InsertTagResult zurück
    }
}
