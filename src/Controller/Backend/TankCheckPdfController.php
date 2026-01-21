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

    public function __invoke(Request $request, string $id): Response
    {
        try {
            $pdfContent = $this->pdfGenerator->generateForBooking($id);
            $booking = DcCheckBookingModel::findByPk($id);

            return new Response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $booking->bookingNumber . '.pdf"'
            ]);
        } catch (RuntimeException $e) {
            return new Response($e->getMessage(), 404);
        }
    }
}
