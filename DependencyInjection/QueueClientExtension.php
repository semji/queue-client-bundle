<?php

namespace ReputationVIP\Bundle\QueueClientBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class QueueClientExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $adapterChoice = array(
            'sqs' => 'ReputationVIP\QueueClient\Adapter\SQSAdapter',
            'file' => 'ReputationVIP\QueueClient\Adapter\FileAdapter',
            'memory' => 'ReputationVIP\QueueClient\Adapter\MemoryAdapter',
            'null' => 'ReputationVIP\QueueClient\Adapter\NullAdapter'
        );
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');

        if (!isset($adapterChoice[$config['adapter']['type']])) {
            throw new \InvalidArgumentException('Unknown handler type : ' . $config['adapter']['type']);
        }

        $container->setParameter(
            'queue_client.adapter.priority_handler.class',
            $config['priority_handler']
        );
        $container->setParameter(
            'queue_client.queues_file',
            $config['queues_file']
        );
        $container->setParameter(
            'queue_client.adapter.class',
            $adapterChoice[$config['adapter']['type']]
        );
        $container->setParameter(
            'queue_client.config',
            $config['adapter']
        );
    }
}
