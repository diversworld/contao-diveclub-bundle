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
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class CopyFilesMigration extends AbstractMigration
{
    private readonly Filesystem $fs;

    public function __construct()
    {
        $this->fs = new Filesystem();
    }

    public function getName(): string
    {
        return 'Contao Diveclub Bundle: Copy sample data-files';
    }

    public function shouldRun(): bool
    {
        return !$this->fs->exists('files/diveclub');
    }

    public function run(): MigrationResult
    {
        $output = new ConsoleOutput(); // Nutze Symfony's ConsoleOutput

        $path = \sprintf(
            '%s/%s/bundles/diversworldcontaodiveclub/templates',
            self::getRootDir(),
            self::getWebDir(),
        );

        $output->writeln('<info>Starte den Kopiervorgang...</info>');

        new Folder('files/diveclub');

        $output->writeln('<comment>Ordner "files/diveclub" wurde erstellt.</comment>');
        $output->writeln('<info>Path:'. $path . '</info>');

        $this->getFiles($path);
        $output->writeln('<info>Kopiervorgang abgeschlossen!</info>');

        return $this->createResult(true);
    }

    public static function getRootDir(): string
    {
        return System::getContainer()->getParameter('kernel.project_dir');
    }

    public static function getWebDir(): string
    {
        dump(StringUtil::stripRootDir(System::getContainer()->getParameter('contao.web_dir')));
        return StringUtil::stripRootDir(System::getContainer()->getParameter('contao.web_dir'));
    }

    protected function getFiles(string $path): void
    {
        foreach (Folder::scan($path) as $dir) {
            if (!is_dir($path . '/' . $dir)) {
                $pos = strpos($path, 'diversworldcontaodiveclub');
                $filesFolder = 'files/diveclub' . str_replace('diversworldcontaodiveclub', '', substr($path, $pos)) . '/' . $dir;
                if (!$this->fs->exists(self::getRootDir() . '/' . $filesFolder)) {
                    $objFile = new File(self::getWebDir() . '/bundles/' . substr($path, $pos) . '/' . $dir);
                    $objFile->copyTo($filesFolder);
                }
            } else {
                $folder = $path . '/' . $dir;
                $pos = strpos($path, 'diversworldcontaodiveclub');
                $filesFolder = 'files/diveclub' . str_replace('diversworldcontaodiveclub', '', substr($path, $pos)) . '/' . $dir;
                if (!$this->fs->exists($filesFolder)) {
                    new Folder($filesFolder);
                }
                $this->getFiles($folder);
            }
        }
    }
}
