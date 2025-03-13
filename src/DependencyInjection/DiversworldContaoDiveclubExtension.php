<?php

declare(strict_types=1);

/*
 * This file is part of Diveclub App.
 *
 * (c) Eckhard Becker 2025 <info@diversworld.eu>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/diversworld/contao-diveclub-bundle
 */

namespace Diversworld\ContaoDiveclubBundle\DependencyInjection;

use Diversworld\ContaoDiveclubBundle\ParameterBag\BackendParameterBag;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class DiversworldContaoDiveclubExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function getAlias(): string
    {
        return Configuration::ROOT_KEY;
    }

    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();

        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../../config')
        );

        $loader->load('parameters.yaml');
        $loader->load('services.yaml');
        $loader->load('listener.yaml');

        $rootKey = $this->getAlias();

        $container->setParameter($rootKey.'.module_title', $config['module_title']);
        $container->setParameter($rootKey.'.module_description', $config['module_description']);

        // Werte aus Configuration nach $GLOBALS['TL_LANG'] für MOD übernehmen
        $moduleTitle = $config['module_title'] ?? 'Diveclub Manager'; // Fallback
        $moduleDescription = $config['module_description'] ?? 'Manage equipment, dive courses, etc.'; // Fallback

        $GLOBALS['TL_LANG']['MOD']['diveclub'] = [
            $moduleTitle,
            $moduleDescription,
        ];
    }
}
