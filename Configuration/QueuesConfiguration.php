<?php

namespace ReputationVIP\Bundle\QueuesConfiguration;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class QueuesConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('queues');

        $rootNode
            ->prototype('array')
                ->children()
                    ->scalarNode('name')->end()
                    ->arrayNode('aliases')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()
        ;


        return $treeBuilder;
    }
}
