<?php

namespace ReputationVIP\Bundle\QueueClientBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('queue_client');

        $rootNode
            ->children()
                ->arrayNode('adapter')
                    ->isRequired()
                    ->children()
                        ->scalarNode('type')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('key')->end()
                        ->scalarNode('secret')->end()
                        ->scalarNode('region')
                            ->defaultValue('eu-west-1')
                        ->end()
                        ->scalarNode('version')
                            ->defaultValue('2012-11-05')
                        ->end()
                        ->scalarNode('repository')
                            ->defaultValue('/tmp/queues')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('queues_file')
                    ->defaultValue(__DIR__.'/../Resources/config/queues.yml')
                ->end()
                ->scalarNode('priority_handler')
                    ->defaultValue('ReputationVIP\QueueClient\PriorityHandler\StandardPriorityHandler')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
