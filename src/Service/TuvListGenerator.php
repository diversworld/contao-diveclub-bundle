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
    private function getOrdersData(int $proposalId): array // Methode zum Abrufen der Bestelldaten für einen bestimmten Vorschlag
    {
        $this->framework->initialize(); // Initialisiere das Contao-Framework für Datenbankzugriffe

        $proposal = DcCheckProposalModel::findByPk($proposalId); // Suche den Vorschlag anhand der Primärschlüssel-ID

        if (null === $proposal) { // Falls kein Vorschlag gefunden wurde
            throw new RuntimeException('Proposal not found'); // Wirf eine Fehlermeldung
        }

        // Finde alle Buchungen für diesen Vorschlag
        $bookings = DcCheckBookingModel::findBy('pid', $proposal->id); // Suche Buchungen mit der PID des Vorschlags

        $data = []; // Initialisiere das Daten-Array
        if ($bookings) { // Wenn Buchungen vorhanden sind
            foreach ($bookings as $booking) { // Iteriere über jede einzelne Buchung
                $bookingOrders = DcCheckOrderModel::findBy('pid', $booking->id); // Suche die zugehörigen Bestellpositionen (Geräte)
                if ($bookingOrders) { // Wenn Bestellungen vorhanden sind
                    foreach ($bookingOrders as $order) { // Iteriere über jede einzelne Bestellung
                        $data[] = [ // Füge die aufbereiteten Daten zum Array hinzu
                            'member_name' => $booking->firstname . ' ' . $booking->lastname, // Vollständiger Name des Mitglieds
                            'serialNumber' => (string)$order->serialNumber, // Seriennummer des Geräts
                            'size' => $order->size . ' L', // Größe des Geräts mit Einheit Liter
                            'manufacturer' => (string)$order->manufacturer, // Hersteller des Geräts
                            'o2clean' => ($order->o2clean ? 'Ja' : 'Nein') // O2-Clean Status als Text (Ja/Nein)
                        ];
                    }
                }
            }
        }

        return $data; // Gib das Array mit allen gesammelten Daten zurück
    }

    public function generatePdf(int $proposalId): string // Methode zur Generierung einer PDF-Datei
    {
        $proposal = DcCheckProposalModel::findByPk($proposalId); // Lade den Vorschlag für Metadaten
        $ordersData = $this->getOrdersData($proposalId); // Hole die aufbereiteten Bestelldaten

        $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false); // Erstelle eine neue FPDI-Instanz (TCPDF Erweiterung) in A4 Hochformat

        // Set document information
        $pdf->SetCreator('TCPDF'); // Setze den Ersteller des Dokuments
        $pdf->SetAuthor('Diveclub'); // Setze den Autor des Dokuments
        $pdf->SetTitle('TÜV-Liste ' . $proposal->title); // Setze den Titel des PDF-Dokuments
        $pdf->SetSubject('Liste der Tauchgeräte für TÜV-Prüfung'); // Setze das Thema des Dokuments

        // Remove default header/footer
        $pdf->setPrintHeader(false); // Deaktiviere den Standard-Header von TCPDF
        $pdf->setPrintFooter(false); // Deaktiviere den Standard-Footer von TCPDF

        // Set margins
        $pdf->SetMargins(15, 20, 15); // Setze die Seitenränder (Links, Oben, Rechts) in mm
        $pdf->SetAutoPageBreak(TRUE, 25); // Aktiviere automatischen Seitenumbruch mit Abstand zum unteren Rand

        $pdf->AddPage(); // Füge eine neue Seite hinzu

        $pdf->SetFont('helvetica', 'B', 16); // Setze die Schriftart auf Helvetica, Fett, Größe 16
        $pdf->Cell(0, 10, 'TÜV-Prüfungsliste', 0, 1, 'C'); // Erzeuge eine zentrierte Überschrift

        $pdf->SetFont('helvetica', '', 12); // Setze die Schriftart auf Helvetica, Normal, Größe 12
        $pdf->Cell(0, 10, 'Termin: ' . $proposal->title . ' (' . ($proposal->proposalDate ? date('d.m.Y', (int)$proposal->proposalDate) : '-') . ')', 0, 1, 'C'); // Zeige Termin-Details zentriert an
        $pdf->Ln(5); // Füge einen Zeilenumbruch mit 5mm Abstand hinzu

        $pdf->SetFont('helvetica', 'B', 10); // Setze Schriftart für den Tabellen-Header (Fett, Größe 10)
        // Header
        $pdf->Cell(45, 7, 'Kunde', 1); // Spalte Kunde
        $pdf->Cell(40, 7, 'Seriennummer', 1); // Spalte Seriennummer
        $pdf->Cell(20, 7, 'Größe', 1); // Spalte Größe
        $pdf->Cell(45, 7, 'Hersteller', 1); // Spalte Hersteller
        $pdf->Cell(30, 7, 'O2-Clean', 1); // Spalte O2-Clean
        $pdf->Ln(); // Zeilenumbruch nach dem Header

        $pdf->SetFont('helvetica', '', 10); // Setze Schriftart für den Tabelleninhalt (Normal, Größe 10)

        if (empty($ordersData)) { // Falls keine Daten vorhanden sind
            $pdf->Cell(0, 10, 'Keine Geräte für diesen Termin gebucht.', 1, 1, 'C'); // Zeige Hinweis in der Tabelle an
        } else { // Wenn Daten vorhanden sind
            foreach ($ordersData as $row) { // Iteriere über jede Datenzeile
                $pdf->Cell(45, 7, $row['member_name'], 1); // Zelle für Kundenname
                $pdf->Cell(40, 7, $row['serialNumber'], 1); // Zelle für Seriennummer
                $pdf->Cell(20, 7, $row['size'], 1); // Zelle für Größe
                $pdf->Cell(45, 7, $row['manufacturer'], 1); // Zelle für Hersteller
                $pdf->Cell(30, 7, $row['o2clean'], 1); // Zelle für O2-Clean Status
                $pdf->Ln(); // Zeilenumbruch nach der Datenzeile
            }
        }

        return $pdf->Output('TUV-Liste.pdf', 'S'); // Gib das generierte PDF als String zurück (S = String)
    }

    public function generateCsv(int $proposalId): string // Methode zur Generierung einer CSV-Datei
    {
        $ordersData = $this->getOrdersData($proposalId); // Hole die Bestelldaten

        $fp = fopen('php://temp', 'r+'); // Öffne einen temporären Speicherstream zum Schreiben

        // UTF-8 BOM für Excel
        fputs($fp, "\xEF\xBB\xBF"); // Schreibe den Byte Order Mark für korrekte UTF-8 Erkennung in Excel

        // Header
        fputcsv($fp, ['Kunde', 'Seriennummer', 'Größe', 'Hersteller', 'O2-Clean'], ';'); // Schreibe die Kopfzeile mit Semikolon als Trenner

        foreach ($ordersData as $row) { // Iteriere über alle Datenzeilen
            fputcsv($fp, [ // Schreibe jede Zeile in den CSV-Stream
                $row['member_name'],
                $row['serialNumber'],
                $row['size'],
                $row['manufacturer'],
                $row['o2clean']
            ], ';'); // Verwende Semikolon als Spaltentrennzeichen
        }

        rewind($fp); // Setze den Zeiger des Streams an den Anfang zurück
        $csv = stream_get_contents($fp); // Lies den gesamten Inhalt des Streams in eine Variable
        fclose($fp); // Schließe den temporären Stream

        return (string)$csv; // Gib den CSV-Inhalt als String zurück
    }

    public function generateXlsx(int $proposalId): string // Methode zur Generierung einer XLSX-Datei (Excel)
    {
        // Da wir keine externen Libs wie PhpSpreadsheet garantieren können,
        // generieren wir ein HTML-basiertes Excel (XML), das Excel problemlos öffnet.
        // Oder wir nutzen CSV als Fallback, aber der User wollte XLSX.
        // In Contao Projekten ist PhpSpreadsheet oft via contao/core-bundle dabei.

        if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) { // Prüfe ob die PhpSpreadsheet Bibliothek verfügbar ist
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet(); // Erstelle eine neue Arbeitsmappe
            $sheet = $spreadsheet->getActiveSheet(); // Hole das aktuell aktive Arbeitsblatt

            $ordersData = $this->getOrdersData($proposalId); // Hole die Bestelldaten

            // Header
            $sheet->setCellValue('A1', 'Kunde'); // Setze Header für Spalte A
            $sheet->setCellValue('B1', 'Seriennummer'); // Setze Header für Spalte B
            $sheet->setCellValue('C1', 'Größe'); // Setze Header für Spalte C
            $sheet->setCellValue('D1', 'Hersteller'); // Setze Header für Spalte D
            $sheet->setCellValue('E1', 'O2-Clean'); // Setze Header für Spalte E

            $rowNum = 2; // Beginne mit dem Schreiben der Daten in Zeile 2
            foreach ($ordersData as $row) { // Iteriere über alle Datenzeilen
                $sheet->setCellValue('A' . $rowNum, $row['member_name']); // Schreibe Kundenname in Spalte A
                $sheet->setCellValue('B' . $rowNum, $row['serialNumber']); // Schreibe Seriennummer in Spalte B
                $sheet->setCellValue('C' . $rowNum, $row['size']); // Schreibe Größe in Spalte C
                $sheet->setCellValue('D' . $rowNum, $row['manufacturer']); // Schreibe Hersteller in Spalte D
                $sheet->setCellValue('E' . $rowNum, $row['o2clean']); // Schreibe O2-Clean Status in Spalte E
                $rowNum++; // Inkrementiere die Zeilennummer
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet); // Erstelle einen Xlsx-Writer für die Arbeitsmappe
            ob_start(); // Starte die Ausgabepufferung
            $writer->save('php://output'); // Schreibe die Excel-Datei in den Ausgabepuffer
            return ob_get_clean(); // Hole den Pufferinhalt und beende die Pufferung
        }

        // Fallback zu CSV falls PhpSpreadsheet nicht da ist (sollte aber bei Contao 5)
        return $this->generateCsv($proposalId); // Nutze die CSV-Generierung als Ersatz
    }
}
