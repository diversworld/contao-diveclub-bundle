<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Service;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\System;
use Contao\StringUtil;
use Contao\Controller;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckArticlesModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckBookingModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckOrderModel;
use Diversworld\ContaoDiveclubBundle\Model\DcConfigModel;
use RuntimeException;
use setasign\Fpdi\Tcpdf\Fpdi;

class TankCheckPdfGenerator
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    public function generateForBooking(int|string $bookingId): string
    {
        $this->framework->initialize();

        $booking = DcCheckBookingModel::findByPk($bookingId);

        if (null === $booking) {
            throw new RuntimeException('Booking not found');
        }

        $orders = DcCheckOrderModel::findBy('pid', $booking->id);

        // Get template from config
        $config = DcConfigModel::findOneBy(['published=?'], [1]);
        $templatePath = null;
        if ($config && $config->invoiceTemplate) {
            $fileModel = FilesModel::findByUuid($config->invoiceTemplate);
            if ($fileModel && is_file(System::getContainer()->getParameter('kernel.project_dir') . '/' . $fileModel->path)) {
                $templatePath = System::getContainer()->getParameter('kernel.project_dir') . '/' . $fileModel->path;
            }
        }

        $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('TCPDF');
        $pdf->SetAuthor('Diveclub');
        $pdf->SetTitle('Rechnung ' . $booking->bookingNumber);
        $pdf->SetSubject('Rechnung für TÜV-Prüfung');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 40, 15);
        $pdf->SetAutoPageBreak(TRUE, 25);

        // Add template as background if exists
        if ($templatePath) {
            $ext = strtolower(pathinfo($templatePath, PATHINFO_EXTENSION));
            try {
                if ($ext === 'pdf') {
                    $pdf->setSourceFile($templatePath);
                    $tplIdx = $pdf->importPage(1);
                    $pdf->AddPage();
                    $pdf->useTemplate($tplIdx, 0, 0, 210, 297, true);
                } else if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $pdf->AddPage();
                    $pdf->Image($templatePath, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
                } else {
                    $pdf->AddPage();
                }
            } catch (\Exception $e) {
                // Fallback if template import fails
                $pdf->AddPage();
            }
        } else {
            $pdf->AddPage();
        }

        $pdf->SetFont('helvetica', '', 12);
        $pdf->SetY(40);

        $html = '<h1>Rechnung</h1>';
        $html .= '<p>Buchungsnummer: ' . $booking->bookingNumber . '</p>';
        $html .= '<p>Datum: ' . date('d.m.Y', $booking->bookingDate ?: time()) . '</p>';
        $html .= '<hr>';
        $html .= '<p><strong>Kunde:</strong> ' . $booking->firstname . ' ' . $booking->lastname . '</p>';
        $html .= '<p>Email: ' . $booking->email . '</p>';
        $html .= '<hr>';

        $html .= '<h3>Positionen</h3>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<thead><tr style="background-color:#eee;">
                    <th>Flasche / Seriennummer</th>
                    <th>Größe</th>
                    <th>Hersteller</th>
                    <th>Artikel</th>
                    <th>Preis</th>
                  </tr></thead><tbody>';

        $total = 0;
        if ($orders) {
            foreach ($orders as $order) {
                $articles = '';
                if ($order->selectedArticles) {
                    $selected = StringUtil::deserialize($order->selectedArticles);
                    if (is_array($selected)) {
                        $articleModels = DcCheckArticlesModel::findMultipleByIds($selected);
                        if ($articleModels) {
                            $articleNames = [];
                            foreach ($articleModels as $article) {
                                $articleNames[] = $article->title;
                            }
                            $articles = implode(', ', $articleNames);
                        }
                    }
                }

                $html .= '<tr>
                            <td>' . $order->serialNumber . '</td>
                            <td>' . $order->size . ' L</td>
                            <td>' . $order->manufacturer . '</td>
                            <td>' . $articles . '</td>
                            <td align="right">' . number_format((float)$order->totalPrice, 2, ',', '.') . ' &euro;</td>
                          </tr>';
                $total += (float)$order->totalPrice;
            }
        }

        $html .= '</tbody><tfoot><tr>
                    <td colspan="4" align="right"><strong>Gesamtpreis:</strong></td>
                    <td align="right"><strong>' . number_format($total, 2, ',', '.') . ' &euro;</strong></td>
                  </tr></tfoot></table>';

        if ($config && $config->invoiceText) {
            //Nutze den Insert-Tag Parser von Contao
            $parser = System::getContainer()->get('contao.insert_tag.parser');

            // Stelle sicher, dass die aktuelle Buchungs-ID in der Session bekannt ist,
            // damit deine Insert-Tags (DcCheckInsertTag) darauf zugreifen können.
            System::getContainer()->get('request_stack')->getCurrentRequest()?->getSession()->set('last_tank_check_order', $booking->id);

            $invoiceText = $parser->replace($config->invoiceText);
            $html .= '<div style="margin-top: 20px;">' . $invoiceText . '</div>';
        }

        $pdf->writeHTML($html, true, false, true, false, '');

        // Close and output PDF document
        $pdfContent = $pdf->Output($booking->bookingNumber . '.pdf', 'S');

        // Save PDF to destination
        $pdfFolder = 'files';
        if ($config && $config->pdfFolder) {
            $folderModel = FilesModel::findByUuid($config->pdfFolder);
            if ($folderModel) {
                $pdfFolder = $folderModel->path;
            }
        }

        $projectDir = System::getContainer()->getParameter('kernel.project_dir');
        $fileName = $booking->bookingNumber . '.pdf';
        $filePath = $projectDir . '/' . $pdfFolder . '/' . $fileName;

        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        file_put_contents($filePath, $pdfContent);

        return $pdfContent;
    }
}
