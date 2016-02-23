<?php

namespace ReputationVIP\Bundle\QueueClientBundle;

use ReputationVIP\Bundle\QueueClientBundle\Configuration\QueuesConfiguration;
use ReputationVIP\QueueClient\Adapter\AdapterInterface;
use ReputationVIP\QueueClient\QueueClient;
use ReputationVIP\QueueClient\QueueClientInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class QueueClientFactory
{

    /**
     * @param $item
     * @param $key
     * @param ContainerInterface $container
     */
    public static function resolveParameters(&$item, $key, $container)
    {
        if (!is_array($item)) {
            $matches = [];
            while (preg_match('/(?<=%)(.*?)(?=%)/', $item, $matches)) {
                $param = $matches[0];
                if (!empty($param)) {
                    $item = str_replace('%' . $param . '%', $container->getParameter($param), $item);
                } else {
                    throw new \InvalidArgumentException('Empty parameter!');
                }
            }
        }
    }

    /**
     * @param ContainerInterface $container
     * @param AdapterInterface $adapter
     * @param string $queuesFile
     * @return null|QueueClientInterface
     * @throws \ErrorException
     */
    public function get($container, $adapter, $queuesFile)
    {
        $queueClient = new QueueClient($adapter);
        $processor = new Processor();
        $configuration = new QueuesConfiguration();
        $processedConfiguration = $processor->processConfiguration($configuration, Yaml::parse(file_get_contents($queuesFile)));

        array_walk_recursive($processedConfiguration, 'ReputationVIP\Bundle\QueueClientBundle\QueueClientFactory::resolveParameters', $container);
        foreach ($processedConfiguration[QueuesConfiguration::QUEUES_NODE] as $queue) {
            $queueName = $queue[QueuesConfiguration::QUEUE_NAME_NODE];
            foreach ($queue[QueuesConfiguration::QUEUE_ALIASES_NODE] as $alias) {
                try {
                    $queueClient->addAlias($queueName, $alias);
                } catch (\ErrorException $e) {
                    if ($e->getSeverity() === E_ERROR) {
                        throw $e;
                    }
                }
            }
        }

        return $queueClient;
    }
}
