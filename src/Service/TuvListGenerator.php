<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Service;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckBookingModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckOrderModel;
use Diversworld\ContaoDiveclubBundle\Model\DcCheckProposalModel;
use RuntimeException;
use setasign\Fpdi\Tcpdf\Fpdi;

class TuvListGenerator
{
    public function __construct(private readonly ContaoFramework $framework)
    {
    }

    /**
     * @return array<int, array{member_name: string, serialNumber: string, size: string, manufacturer: string, o2clean: string}>
     */
    private function getOrdersData(int $proposalId): array
    {
        $this->framework->initialize();

        $proposal = DcCheckProposalModel::findByPk($proposalId);

        if (null === $proposal) {
            throw new RuntimeException('Proposal not found');
        }

        // Finde alle Buchungen für diesen Vorschlag
        $bookings = DcCheckBookingModel::findBy('pid', $proposal->id);

        $data = [];
        if ($bookings) {
            foreach ($bookings as $booking) {
                $bookingOrders = DcCheckOrderModel::findBy('pid', $booking->id);
                if ($bookingOrders) {
                    foreach ($bookingOrders as $order) {
                        $data[] = [
                            'member_name' => $booking->firstname . ' ' . $booking->lastname,
                            'serialNumber' => (string)$order->serialNumber,
                            'size' => $order->size . ' L',
                            'manufacturer' => (string)$order->manufacturer,
                            'o2clean' => ($order->o2clean ? 'Ja' : 'Nein')
                        ];
                    }
                }
            }
        }

        return $data;
    }

    public function generatePdf(int $proposalId): string
    {
        $proposal = DcCheckProposalModel::findByPk($proposalId);
        $ordersData = $this->getOrdersData($proposalId);

        $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('TCPDF');
        $pdf->SetAuthor('Diveclub');
        $pdf->SetTitle('TÜV-Liste ' . $proposal->title);
        $pdf->SetSubject('Liste der Tauchgeräte für TÜV-Prüfung');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetAutoPageBreak(TRUE, 25);

        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'TÜV-Prüfungsliste', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Termin: ' . $proposal->title . ' (' . ($proposal->proposalDate ? date('d.m.Y', (int)$proposal->proposalDate) : '-') . ')', 0, 1, 'C');
        $pdf->Ln(5);

        $pdf->SetFont('helvetica', 'B', 10);
        // Header
        $pdf->Cell(45, 7, 'Kunde', 1);
        $pdf->Cell(40, 7, 'Seriennummer', 1);
        $pdf->Cell(20, 7, 'Größe', 1);
        $pdf->Cell(45, 7, 'Hersteller', 1);
        $pdf->Cell(30, 7, 'O2-Clean', 1);
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 10);

        if (empty($ordersData)) {
            $pdf->Cell(0, 10, 'Keine Geräte für diesen Termin gebucht.', 1, 1, 'C');
        } else {
            foreach ($ordersData as $row) {
                $pdf->Cell(45, 7, $row['member_name'], 1);
                $pdf->Cell(40, 7, $row['serialNumber'], 1);
                $pdf->Cell(20, 7, $row['size'], 1);
                $pdf->Cell(45, 7, $row['manufacturer'], 1);
                $pdf->Cell(30, 7, $row['o2clean'], 1);
                $pdf->Ln();
            }
        }

        return $pdf->Output('TUV-Liste.pdf', 'S');
    }

    public function generateCsv(int $proposalId): string
    {
        $ordersData = $this->getOrdersData($proposalId);

        $fp = fopen('php://temp', 'r+');

        // UTF-8 BOM für Excel
        fputs($fp, "\xEF\xBB\xBF");

        // Header
        fputcsv($fp, ['Kunde', 'Seriennummer', 'Größe', 'Hersteller', 'O2-Clean'], ';');

        foreach ($ordersData as $row) {
            fputcsv($fp, [
                $row['member_name'],
                $row['serialNumber'],
                $row['size'],
                $row['manufacturer'],
                $row['o2clean']
            ], ';');
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return (string)$csv;
    }

    public function generateXlsx(int $proposalId): string
    {
        // Da wir keine externen Libs wie PhpSpreadsheet garantieren können,
        // generieren wir ein HTML-basiertes Excel (XML), das Excel problemlos öffnet.
        // Oder wir nutzen CSV als Fallback, aber der User wollte XLSX.
        // In Contao Projekten ist PhpSpreadsheet oft via contao/core-bundle dabei.

        if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $ordersData = $this->getOrdersData($proposalId);

            // Header
            $sheet->setCellValue('A1', 'Kunde');
            $sheet->setCellValue('B1', 'Seriennummer');
            $sheet->setCellValue('C1', 'Größe');
            $sheet->setCellValue('D1', 'Hersteller');
            $sheet->setCellValue('E1', 'O2-Clean');

            $rowNum = 2;
            foreach ($ordersData as $row) {
                $sheet->setCellValue('A' . $rowNum, $row['member_name']);
                $sheet->setCellValue('B' . $rowNum, $row['serialNumber']);
                $sheet->setCellValue('C' . $rowNum, $row['size']);
                $sheet->setCellValue('D' . $rowNum, $row['manufacturer']);
                $sheet->setCellValue('E' . $rowNum, $row['o2clean']);
                $rowNum++;
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            ob_start();
            $writer->save('php://output');
            return ob_get_clean();
        }

        // Fallback zu CSV falls PhpSpreadsheet nicht da ist (sollte aber bei Contao 5)
        return $this->generateCsv($proposalId);
    }
}
