<?php

namespace Diversworld\ContaoDiveclubBundle\Controller;

use Contao\CoreBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReservationController extends AbstractController
{
    #[Route('/reservation', name: 'reservation_save')]
    public function saveReservation(Request $request): Response
    {
        // CSRF-Token überprüfen
        if (!$this->isCsrfTokenValid('reservation', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Ungültiges CSRF-Token. Bitte laden Sie die Seite neu.');
        }

        // Hole Daten aus dem Formular
        $assetId = $request->request->get('assetId');

        if (!$assetId) {
            return new Response('Kein Asset ausgewählt!', 400);
        }

        // Reservierung speichern (z. B. in der Datenbank)
        return new Response(sprintf('Asset %s wurde erfolgreich reserviert!', $assetId), 200);
    }
}
