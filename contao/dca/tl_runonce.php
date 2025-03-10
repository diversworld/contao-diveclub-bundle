<?php

namespace Diversworld\ContaoDiveclubBundle\Dca;

use Contao\FilesModel;
use Contao\Folder;
use Contao\System;

class tl_runonce
{
    public function __construct()
    {
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        // Zielpfad im files-Verzeichnis
        $targetFolder = 'files/diveclub/templates/';

        // Prüfen, ob der Ordner bereits existiert
        if (!is_dir($rootDir . '/' . $targetFolder)) {
            // Ordner erstellen
            $folder = new Folder($targetFolder);
        }

        // Quellordner (muss angepasst werden)
        $sourcePath = $rootDir . '/system/modules/contao-diveclub-bundle/templates/';

        // Dateien aus dem Quellordner kopieren
        $files = glob($sourcePath . '*.txt');

        foreach ($files as $file) {
            $filename = basename($file); // Nur den Dateinamen extrahieren

            // Kopieren der Datei in den Zielordner (falls nicht vorhanden)
            $targetFilePath = $rootDir . '/' . $targetFolder . '/' . $filename;
            if (!file_exists($targetFilePath)) {
                copy($file, $targetFilePath);

                // In der Contao-Datenbank registrieren, sofern nötig
                FilesModel::addToDb($targetFolder . '/' . $filename);
            }
        }
    }
}

// Instanziieren und ausführen
new tl_runonce();
