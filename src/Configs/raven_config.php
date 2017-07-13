<?php

namespace Vendi\CLI\Configs;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class raven_config implements ConfigurationInterface
{
    const CONFIG_NAME = 'raven';

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root( self::CONFIG_NAME );

        $rootNode
            ->children()
                ->scalarNode( 'web_hook_url' )
                ->end()
            ->end()
        ;

        // ... add node definitions to the root of the tree

        return $treeBuilder;
    }
}
