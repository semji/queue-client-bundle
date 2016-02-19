<?php

namespace ReputationVIP\Bundle\QueueClientBundle\Configuration;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class QueuesConfiguration implements ConfigurationInterface
{
    const ROOT_NODE = 'queue_client';
    const QUEUES_NODE = 'queues';
    const QUEUE_NAME_NODE = 'name';
    const QUEUE_ALIASES_NODE = 'aliases';

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(static::ROOT_NODE);

        $rootNode
            ->children()
                ->arrayNode(static::QUEUES_NODE)
                ->isRequired()
                    ->prototype('array')
                        ->children()
                            ->scalarNode(static::QUEUE_NAME_NODE)
                                ->isRequired()
                            ->end()
                            ->arrayNode(static::QUEUE_ALIASES_NODE)
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
