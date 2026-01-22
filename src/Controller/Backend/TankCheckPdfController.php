<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Controller\Backend;

use Diversworld\ContaoDiveclubBundle\Model\DcCheckBookingModel;
use Diversworld\ContaoDiveclubBundle\Service\TankCheckPdfGenerator;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contao/dc_check_order_pdf/{id}', name: 'dc_check_order_pdf', defaults: ['_scope' => 'backend', '_token_check' => true])]
class TankCheckPdfController
{
    public function __construct(private readonly TankCheckPdfGenerator $pdfGenerator)
    {
    }

    public function __invoke(Request $request, string $id): Response // Hauptmethode des Controllers zur PDF-Generierung für einen Tank-Check
    {
        try { // Versuche das PDF zu generieren
            $pdfContent = $this->pdfGenerator->generateForBooking($id); // Rufe den Generator auf, um den PDF-Inhalt für die Buchung zu erstellen
            $booking = DcCheckBookingModel::findByPk($id); // Lade das Buchungsmodell anhand der ID für den Dateinamen

            return new Response($pdfContent, 200, [ // Gib eine erfolgreiche HTTP-Response mit dem PDF zurück
                'Content-Type' => 'application/pdf', // Setze den Content-Type auf PDF
                'Content-Disposition' => 'inline; filename="' . $booking->bookingNumber . '.pdf"' // Zeige PDF im Browser an mit der Buchungsnummer als Dateiname
            ]);
        } catch (RuntimeException $e) { // Fange Fehler während der Generierung ab
            return new Response($e->getMessage(), 404); // Gib die Fehlermeldung mit Status 404 zurück
        }
    }
}
