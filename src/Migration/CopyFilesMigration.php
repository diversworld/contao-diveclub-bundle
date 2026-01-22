<?php

declare(strict_types=1);

/*
 * This file is part of ContaoDiveclubBundle.
 *
 * @copyright  Copyright (c) 2025, Diversworld
 * @author     diversworld <https://blog.diversworld.eu>
 * @license    LGPL-3.0-or-later
 */

namespace Diversworld\ContaoDiveclubBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\File;
use Contao\Folder;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\Filesystem\Filesystem;

class CopyFilesMigration extends AbstractMigration // Klasse für die Migration von Beispieldateien
{
    private readonly Filesystem $fs; // Variable für das Symfony Filesystem Tool

    public function __construct() // Konstruktor der Migrationsklasse
    {
        $this->fs = new Filesystem(); // Initialisierung des Filesystems
    }

    public function getName(): string // Gibt den Namen der Migration zurück
    {
        return 'Contao Diveclub Bundle: Copy sample data-files'; // Anzeige-Name im Contao Manager / Installtool
    }

    public function shouldRun(): bool // Prüft, ob die Migration ausgeführt werden muss
    {
        return !$this->fs->exists('files/diveclub'); // Migration ausführen, wenn der Zielordner noch nicht existiert
    }

    public function run(): MigrationResult // Führt die eigentliche Migration aus
    {
        $path = \sprintf( // Ermittle den Pfad zu den Vorlagen im Bundle-Verzeichnis
            '%s/%s/bundles/diversworldcontaodiveclub/templates',
            self::getRootDir(),
            self::getWebDir(),
        );

        new Folder('files/diveclub'); // Erstelle den Zielordner im Projekt-Dateisystem

        $this->getFiles($path); // Kopiere alle Dateien aus dem Bundle-Verzeichnis in das Projekt

        return $this->createResult(true); // Gib ein erfolgreiches Migrationsergebnis zurück
    }

    public static function getRootDir(): string // Ermittelt das Wurzelverzeichnis des Projekts
    {
        return System::getContainer()->getParameter('kernel.project_dir'); // Nutze den Symfony-Parameter für das Projektverzeichnis
    }

    public static function getWebDir(): string // Ermittelt das Web-Verzeichnis (z.B. public oder web)
    {
        return StringUtil::stripRootDir(System::getContainer()->getParameter('contao.web_dir')); // Entferne das Root-Verzeichnis vom Web-Pfad
    }

    protected function getFiles(string $path): void // Rekursive Methode zum Kopieren von Dateien und Ordnern
    {
        foreach (Folder::scan($path) as $dir) { // Scanne den aktuellen Pfad nach Inhalten
            if (!is_dir($path . '/' . $dir)) { // Wenn es sich um eine Datei handelt
                $pos = strpos($path, 'diversworldcontaodiveclub'); // Finde die Position des Bundle-Namens im Pfad
                $filesFolder = 'files/diveclub' . str_replace('diversworldcontaodiveclub', '', substr($path, $pos)) . '/' . $dir; // Berechne den Zielpfad im files-Ordner
                if (!$this->fs->exists(self::getRootDir() . '/' . $filesFolder)) { // Wenn die Datei am Zielort noch nicht existiert
                    $objFile = new File(self::getWebDir() . '/bundles/' . substr($path, $pos) . '/' . $dir); // Erstelle ein Contao-File Objekt der Quelldatei
                    $objFile->copyTo($filesFolder); // Kopiere die Datei an den Zielort
                }
            } else { // Wenn es sich um einen Ordner handelt
                $folder = $path . '/' . $dir; // Setze den neuen Pfad für die Rekursion
                $pos = strpos($path, 'diversworldcontaodiveclub'); // Finde Bundle-Name im Pfad
                $filesFolder = 'files/diveclub' . str_replace('diversworldcontaodiveclub', '', substr($path, $pos)) . '/' . $dir; // Berechne den Zielordner-Pfad
                if (!$this->fs->exists($filesFolder)) { // Wenn der Zielordner nicht existiert
                    new Folder($filesFolder); // Erstelle den neuen Ordner im Zielverzeichnis
                }
                $this->getFiles($folder); // Rufe die Methode rekursiv für den Unterordner auf
            }
        }
    }
}
