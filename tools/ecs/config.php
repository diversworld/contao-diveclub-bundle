<?php

declare(strict_types=1);

// Lade den Composer-Autoloader
if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} elseif (is_file(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} elseif (is_file(__DIR__ . '/../../../../vendor/autoload.php')) {
    // Wenn das Bundle in vendor/diversworld/contao-diveclub-bundle liegt
    require_once __DIR__ . '/../../../../vendor/autoload.php';
}

use Symplify\EasyCodingStandard\Config\ECSConfig;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;

return static function (ECSConfig $ecsConfig): void {

    $contaoConfig = null;

    if (is_file(__DIR__.'/vendor/contao/easy-coding-standard/config/contao.php')) {
        $contaoConfig = __DIR__.'/vendor/contao/easy-coding-standard/config/contao.php';
    } elseif (is_file(__DIR__.'/../../vendor/contao/easy-coding-standard/config/contao.php')) {
        $contaoConfig = __DIR__.'/../../vendor/contao/easy-coding-standard/config/contao.php';
    } elseif (is_file(__DIR__.'/../../../../vendor/contao/easy-coding-standard/config/contao.php')) {
        $contaoConfig = __DIR__.'/../../../../vendor/contao/easy-coding-standard/config/contao.php';
    }

    if ($contaoConfig) {
        $ecsConfig->sets([$contaoConfig]);
    }

    $ecsConfig->ruleWithConfiguration(HeaderCommentFixer::class, [
        'header' => "This file is part of Contao Diveclub Bundle.\n\n(c) Eckhard Becker " . date('Y') . " <info@diversworld.eu>\n@license GPL-3.0-or-later\nFor the full copyright and license information,\nplease view the LICENSE file that was distributed with this source code.\n@link https://github.com/diversworld/contao-diveclub-bundle",
        'location' => 'after_declare_strict',
    ]);

    $ecsConfig->skip([
        '*/contao/dca*',
        '*/contao/languages*',
        MethodChainingIndentationFixer::class => [
            'DependencyInjection/Configuration.php',
        ],
    ]);

    $ecsConfig->parallel();
    $ecsConfig->lineEnding("\n");
    $ecsConfig->cacheDirectory(sys_get_temp_dir().'/ecs_default_cache');
};
