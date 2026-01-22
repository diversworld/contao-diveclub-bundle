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

    public function generateForBooking(int|string $bookingId): string // Hauptmethode zur Generierung des Tank-Check PDF für eine Buchung
    {
        $this->framework->initialize(); // Initialisiere das Contao-Framework für Datenbankzugriffe

        $booking = DcCheckBookingModel::findByPk($bookingId); // Lade das Buchungs-Modell anhand der ID

        if (null === $booking) { // Falls die Buchung nicht existiert
            throw new RuntimeException('Booking not found'); // Wirf eine Fehlermeldung
        }

        $orders = DcCheckOrderModel::findBy('pid', $booking->id); // Suche alle zugehörigen Bestellpositionen (Geräte)

        // Get template from config
        $config = DcConfigModel::findOneBy(['published=?'], [1]); // Lade die aktive Konfiguration
        $templatePath = null; // Initialisiere den Pfad zur Vorlage
        if ($config && $config->invoiceTemplate) { // Falls eine Rechnungsvorlage konfiguriert ist
            $fileModel = FilesModel::findByUuid($config->invoiceTemplate); // Hole das FilesModel über die UUID
            if ($fileModel && is_file(System::getContainer()->getParameter('kernel.project_dir') . '/' . $fileModel->path)) { // Wenn die Datei existiert
                $templatePath = System::getContainer()->getParameter('kernel.project_dir') . '/' . $fileModel->path; // Setze den absoluten Pfad zur Vorlage
            }
        }

        $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false); // Erstelle neue PDF-Instanz (TCPDF/FPDI)

        // Set document information
        $pdf->SetCreator('TCPDF'); // Setze Ersteller
        $pdf->SetAuthor('Diveclub'); // Setze Autor
        $pdf->SetTitle('Rechnung ' . $booking->bookingNumber); // Setze Dokumenttitel
        $pdf->SetSubject('Rechnung für TÜV-Prüfung'); // Setze Thema

        // Remove default header/footer
        $pdf->setPrintHeader(false); // Header deaktivieren
        $pdf->setPrintFooter(false); // Footer deaktivieren

        // Set margins
        $pdf->SetMargins(15, 40, 15); // Seitenränder setzen
        $pdf->SetAutoPageBreak(TRUE, 25); // Automatischer Seitenumbruch

        // Add template as background if exists
        if ($templatePath) { // Falls ein Hintergrund-Template vorhanden ist
            $ext = strtolower(pathinfo($templatePath, PATHINFO_EXTENSION)); // Dateiendung ermitteln
            try {
                if ($ext === 'pdf') { // Falls Template ein PDF ist
                    $pdf->setSourceFile($templatePath); // Setze die Quelldatei
                    $tplIdx = $pdf->importPage(1); // Importiere die erste Seite
                    $pdf->AddPage(); // Füge neue Seite im Dokument hinzu
                    $pdf->useTemplate($tplIdx, 0, 0, 210, 297, true); // Platziere das Template auf der Seite
                } else if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) { // Falls Template ein Bild ist
                    $pdf->AddPage(); // Neue Seite hinzufügen
                    $pdf->Image($templatePath, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0); // Bild als Hintergrund einfügen
                } else {
                    $pdf->AddPage(); // Ohne Template fortfahren
                }
            } catch (\Exception $e) {
                // Fallback if template import fails
                $pdf->AddPage(); // Neue leere Seite bei Fehler
            }
        } else {
            $pdf->AddPage(); // Neue leere Seite wenn kein Template gewählt
        }

        $pdf->SetFont('helvetica', '', 12); // Schriftart setzen
        $pdf->SetY(40); // Vertikale Position setzen

        $html = '<h1>Rechnung</h1>'; // HTML-Inhalt: Überschrift
        $html .= '<p>Buchungsnummer: ' . $booking->bookingNumber . '</p>'; // Buchungsnummer hinzufügen
        $html .= '<p>Datum: ' . date('d.m.Y', $booking->bookingDate ?: time()) . '</p>'; // Datum hinzufügen
        $html .= '<hr>'; // Trennlinie
        $html .= '<p><strong>Kunde:</strong> ' . $booking->firstname . ' ' . $booking->lastname . '</p>'; // Kundendaten hinzufügen
        $html .= '<p>Email: ' . $booking->email . '</p>'; // Email hinzufügen
        $html .= '<hr>'; // Trennlinie

        $html .= '<h3>Positionen</h3>'; // Überschrift Positionen
        $html .= '<table border="1" cellpadding="5">'; // Tabellen-Start
        $html .= '<thead><tr style="background-color:#eee;">
                    <th>Flasche / Seriennummer</th>
                    <th>Größe</th>
                    <th>Hersteller</th>
                    <th>Artikel</th>
                    <th>Preis</th>
                  </tr></thead><tbody>'; // Tabellen-Header

        $total = 0; // Variable für Gesamtsumme
        if ($orders) { // Wenn Bestellpositionen vorhanden sind
            foreach ($orders as $order) { // Über Positionen iterieren
                $articles = ''; // Artikel-String initialisieren
                if ($order->selectedArticles) { // Falls Artikel ausgewählt wurden
                    $selected = StringUtil::deserialize($order->selectedArticles); // Deserialisiere Artikel-IDs
                    if (is_array($selected)) { // Wenn es ein Array ist
                        $articleModels = DcCheckArticlesModel::findMultipleByIds($selected); // Lade Artikel-Modelle
                        if ($articleModels) { // Falls Modelle gefunden wurden
                            $articleNames = []; // Namen-Array initialisieren
                            foreach ($articleModels as $article) { // Über Artikel iterieren
                                $articleNames[] = $article->title; // Namen hinzufügen
                            }
                            $articles = implode(', ', $articleNames); // In Komma-separierten String umwandeln
                        }
                    }
                }

                $html .= '<tr>
                            <td>' . $order->serialNumber . '</td>
                            <td>' . $order->size . ' L</td>
                            <td>' . $order->manufacturer . '</td>
                            <td>' . $articles . '</td>
                            <td align="right">' . number_format((float)$order->totalPrice, 2, ',', '.') . ' &euro;</td>
                          </tr>'; // Tabellenzeile hinzufügen
                $total += (float)$order->totalPrice; // Zum Gesamtpreis addieren
            }
        }

        $html .= '</tbody><tfoot><tr>
                    <td colspan="4" align="right"><strong>Gesamtpreis:</strong></td>
                    <td align="right"><strong>' . number_format($total, 2, ',', '.') . ' &euro;</strong></td>
                  </tr></tfoot></table>'; // Tabellen-Footer mit Gesamtsumme

        if ($config && $config->invoiceText) { // Falls ein Rechnungs-Zusatztext konfiguriert ist
            //Nutze den Insert-Tag Parser von Contao
            $parser = System::getContainer()->get('contao.insert_tag.parser'); // Hole den Insert-Tag-Parser-Service

            // Stelle sicher, dass die aktuelle Buchungs-ID in der Session bekannt ist,
            // damit deine Insert-Tags (DcCheckInsertTag) darauf zugreifen können.
            System::getContainer()->get('request_stack')->getCurrentRequest()?->getSession()->set('last_tank_check_order', $booking->id); // Setze Buchungs-ID in Session für Insert-Tags

            $invoiceText = $parser->replace($config->invoiceText); // Ersetze Insert-Tags im Rechnungstext
            $html .= '<div style="margin-top: 20px;">' . $invoiceText . '</div>'; // Text zum HTML hinzufügen
        }

        $pdf->writeHTML($html, true, false, true, false, ''); // HTML-Inhalt in das PDF schreiben

        // Close and output PDF document
        $pdfContent = $pdf->Output($booking->bookingNumber . '.pdf', 'S'); // PDF generieren und als String zurückgeben

        // Save PDF to destination
        $pdfFolder = 'files'; // Standard-Zielordner
        if ($config && $config->pdfFolder) { // Falls ein spezifischer Ordner konfiguriert wurde
            $folderModel = FilesModel::findByUuid($config->pdfFolder); // Hole das FilesModel
            if ($folderModel) { // Falls Ordner existiert
                $pdfFolder = $folderModel->path; // Pfad aus Modell übernehmen
            }
        }

        $projectDir = System::getContainer()->getParameter('kernel.project_dir'); // Projekt-Wurzelverzeichnis ermitteln
        $fileName = $booking->bookingNumber . '.pdf'; // Dateiname generieren
        $filePath = $projectDir . '/' . $pdfFolder . '/' . $fileName; // Absoluter Zielpfad zur Datei

        if (!is_dir(dirname($filePath))) { // Falls der Zielordner nicht existiert
            mkdir(dirname($filePath), 0777, true); // Ordner rekursiv erstellen
        }

        file_put_contents($filePath, $pdfContent); // PDF-Inhalt permanent auf Festplatte speichern

        return $pdfContent; // PDF-Inhalt zur weiteren Verwendung zurückgeben
    }
}
