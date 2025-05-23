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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_KEY = 'diversworld_contao_diveclub';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_KEY);

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('module_title')
                    ->cannotBeEmpty() // Der Titel darf nicht leer sein
                    ->defaultValue('Diveclub Manager') // Standardwert
                ->end()
                ->scalarNode('module_description')
                    ->defaultValue('This is the Diveclub Manager.') // Optionale Beschreibung
                ->end()
            ->end()
        ;
        return $treeBuilder;
    }
}
