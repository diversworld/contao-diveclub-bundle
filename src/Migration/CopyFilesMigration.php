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
use Symfony\Component\Filesystem\Filesystem;

class CopyFilesMigration extends AbstractMigration // Klasse für die Migration von Beispieldateien
{
    private readonly Filesystem $fs; // Variable für das Symfony Filesystem Tool
    private readonly string $projectDir;
    private readonly string $webDir;

    public function __construct(string $projectDir, string $webDir) // Konstruktor der Migrationsklasse
    {
        $this->fs = new Filesystem(); // Initialisierung des Filesystems
        $this->projectDir = $projectDir;

        // Strip root dir manually to avoid System::getContainer() call in StringUtil::stripRootDir
        $this->webDir = $this->stripRootDir($webDir, $projectDir);
    }

    private function stripRootDir(string $path, string $projectDir): string
    {
        $projectDir = str_replace('\\', '/', $projectDir);
        $path = str_replace('\\', '/', $path);

        if (str_starts_with($path, $projectDir)) {
            return ltrim(substr($path, strlen($projectDir)), '/');
        }

        return $path;
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
            $this->getRootDir(),
            $this->getWebDir(),
        );

        if (!$this->fs->exists($this->getRootDir() . '/files/diveclub')) {
            $this->fs->mkdir($this->getRootDir() . '/files/diveclub');
        }

        $this->getFiles($path); // Kopiere alle Dateien aus dem Bundle-Verzeichnis in das Projekt

        return $this->createResult(true); // Gib ein erfolgreiches Migrationsergebnis zurück
    }

    public function getRootDir(): string // Ermittelt das Wurzelverzeichnis des Projekts
    {
        return $this->projectDir;
    }

    public function getWebDir(): string // Ermittelt das Web-Verzeichnis (z.B. public oder web)
    {
        return $this->webDir;
    }

    protected function getFiles(string $path): void // Rekursive Methode zum Kopieren von Dateien und Ordnern
    {
        if (!is_dir($path)) {
            return;
        }

        $items = array_diff(scandir($path), ['.', '..']);

        foreach ($items as $item) {
            $source = $path . '/' . $item;
            $pos = strpos($path, 'diversworldcontaodiveclub');

            if (false === $pos) {
                continue;
            }

            $relPath = str_replace('diversworldcontaodiveclub', '', substr($path, $pos));
            $targetRelPath = 'files/diveclub' . $relPath . '/' . $item;
            $targetAbsPath = $this->getRootDir() . '/' . $targetRelPath;

            if (is_dir($source)) {
                if (!$this->fs->exists($targetAbsPath)) {
                    $this->fs->mkdir($targetAbsPath);
                }
                $this->getFiles($source);
            } else {
                if (!$this->fs->exists($targetAbsPath)) {
                    $this->fs->copy($source, $targetAbsPath);
                }
            }
        }
    }
}
