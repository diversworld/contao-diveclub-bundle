<?php

declare(strict_types=1);

namespace Diversworld\ContaoDiveclubBundle\Controller\Backend;

use Diversworld\ContaoDiveclubBundle\Model\DcCheckProposalModel;
use Diversworld\ContaoDiveclubBundle\Model\DcConfigModel;
use Diversworld\ContaoDiveclubBundle\Service\TuvListGenerator;
use Contao\FilesModel;
use Contao\System;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contao/dc_tuv_list_export/{id}', name: 'dc_tuv_list_export', defaults: ['_scope' => 'backend', '_token_check' => true])]
class TuvListExportController
{
    public function __construct(private readonly TuvListGenerator $generator)
    {
    }

    public function __invoke(Request $request, string $id): Response // Hauptmethode des Controllers, die bei Aufruf der Route ausgeführt wird
    {
        try { // Versuche den Export auszuführen
            $proposal = DcCheckProposalModel::findByPk($id); // Suche den Prüfvorschlag anhand der übergebenen ID
            if (null === $proposal) { // Wenn kein Vorschlag gefunden wurde
                throw new RuntimeException('Proposal not found'); // Wirf eine Exception mit entsprechender Fehlermeldung
            }

            // Get format from config
            $config = DcConfigModel::findOneBy(['published=?'], [1]); // Suche die aktive Konfiguration in der Datenbank
            $format = $config?->tuvListFormat ?: 'pdf'; // Bestimme das Export-Format, Standard ist PDF

            switch ($format) { // Fallunterscheidung basierend auf dem gewählten Format
                case 'csv': // Falls CSV gewählt wurde
                    $content = $this->generator->generateCsv((int)$id); // Generiere den CSV-Inhalt über den Generator
                    $contentType = 'text/csv'; // Setze den passenden HTTP Content-Type für CSV
                    $extension = 'csv'; // Definiere die Dateiendung als csv
                    break; // Beende den aktuellen Case
                case 'xlsx': // Falls XLSX (Excel) gewählt wurde
                    $content = $this->generator->generateXlsx((int)$id); // Generiere den Excel-Inhalt über den Generator
                    $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; // Setze Content-Type für XLSX
                    $extension = 'xlsx'; // Definiere die Dateiendung als xlsx
                    break; // Beende den aktuellen Case
                case 'pdf': // Falls PDF gewählt wurde
                default: // Standardmäßig wird PDF verwendet
                    $content = $this->generator->generatePdf((int)$id); // Generiere den PDF-Inhalt über den Generator
                    $contentType = 'application/pdf'; // Setze den passenden HTTP Content-Type für PDF
                    $extension = 'pdf'; // Definiere die Dateiendung als pdf
                    break; // Beende den aktuellen Case
            }

            $filename = 'TUV-Liste-' . ($proposal->title ?: $id) . '.' . $extension; // Erzeuge den Dateinamen aus Titel und Endung

            // Save the file if a folder is configured
            $projectDir = System::getContainer()->getParameter('kernel.project_dir'); // Ermittle das Wurzelverzeichnis des Projekts
            $savePath = $projectDir . '/files'; // Setze den Standard-Pfad auf den "files" Ordner

            if ($config?->tuvListFolder) { // Prüfe ob in der Konfiguration ein spezieller Ordner hinterlegt ist
                $folderModel = FilesModel::findByPk($config->tuvListFolder); // Hole das FilesModel für den gewählten Ordner
                if ($folderModel && is_dir($projectDir . '/' . $folderModel->path)) { // Wenn der Ordner existiert
                    $savePath = $projectDir . '/' . $folderModel->path; // Aktualisiere den Speicherpfad auf den gewählten Ordner
                }
            }

            if (!is_dir($savePath)) { // Falls der Zielpfad noch nicht existiert
                mkdir($savePath, 0777, true); // Erstelle den Ordner inklusive Unterverzeichnissen rekursiv
            }

            file_put_contents($savePath . '/' . $filename, $content); // Schreibe den generierten Inhalt in die Datei am Zielort

            return new Response($content, 200, [ // Gib eine erfolgreiche HTTP-Response mit dem Dateiinhalt zurück
                'Content-Type' => $contentType, // Setze den zuvor bestimmten Content-Type
                'Content-Disposition' => 'inline; filename="' . $filename . '"' // Erlaube die Anzeige im Browser mit dem generierten Dateinamen
            ]);
        } catch (RuntimeException $e) { // Fange Fehler während der Ausführung ab
            return new Response($e->getMessage(), 404); // Gib die Fehlermeldung mit Status 404 (Not Found) zurück
        }
    }
}
