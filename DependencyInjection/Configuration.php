<?php

namespace Ticketpark\ExpiringUrlBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ticketpark_expiring_url');

        $rootNode
            ->children()
                ->scalarNode('ttl')
                    ->defaultValue(10)
                    ->info('Default time-to-live for urls in minutes')
                ->end()
                ->scalarNode('route_parameter')
                    ->defaultValue('expirationHash')
                    ->info('Route parameter name which contains the expiration hash')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}