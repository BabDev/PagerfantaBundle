<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public const string EXCEPTION_STRATEGY_CUSTOM = 'custom';
    public const string EXCEPTION_STRATEGY_TO_HTTP_NOT_FOUND = 'to_http_not_found';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('babdev_pagerfanta');

        /** @var ArrayNodeDefinition $root */
        $root = $treeBuilder->getRootNode();

        $root
            ->children()
                ->scalarNode('default_view')->defaultValue('default')->end()
                ->scalarNode('default_twig_template')->defaultValue('@BabDevPagerfanta/default.html.twig')->end()
                ->arrayNode('exceptions_strategy')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('out_of_range_page')
                            ->defaultValue(self::EXCEPTION_STRATEGY_TO_HTTP_NOT_FOUND)
                            ->values([self::EXCEPTION_STRATEGY_TO_HTTP_NOT_FOUND, self::EXCEPTION_STRATEGY_CUSTOM])
                        ->end()
                        ->enumNode('not_valid_current_page')
                            ->defaultValue(self::EXCEPTION_STRATEGY_TO_HTTP_NOT_FOUND)
                            ->values([self::EXCEPTION_STRATEGY_TO_HTTP_NOT_FOUND, self::EXCEPTION_STRATEGY_CUSTOM])
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
