<?php

namespace ReputationVIP\Bundle\QueueClientBundle;

use ReputationVIP\QueueClient\Adapter\AdapterInterface;
use ReputationVIP\QueueClient\QueueClient;
use ReputationVIP\QueueClient\QueueClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class QueueClientFactory
{
    const QUEUES_NODE = 'queues';
    const QUEUE_NAME_NODE = 'name';
    const QUEUE_ALIASES_NODE = 'aliases';

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
                }
                else {
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

        $yml = Yaml::parse(file_get_contents($queuesFile));
        array_walk_recursive($yml, 'ReputationVIP\Bundle\QueueClientBundle\QueueClientFactory::resolveParameters', $container);
        if (array_key_exists(static::QUEUES_NODE, $yml)) {
            if (null === $yml[static::QUEUES_NODE]) {
                throw new \InvalidArgumentException('Empty ' . static::QUEUES_NODE . ' node.');
            }
            foreach ($yml[static::QUEUES_NODE] as $queue) {
                $queueName = $queue[static::QUEUE_NAME_NODE];
                if (!empty($queue[static::QUEUE_ALIASES_NODE])) {
                    foreach ($queue[static::QUEUE_ALIASES_NODE] as $alias) {
                        try {
                            $queueClient->addAlias($queueName, $alias);
                        } catch (\ErrorException $e) {
                            if ($e->getSeverity() === E_ERROR) {
                                throw $e;
                            }
                        }
                    }
                }
            }
        } else {
            throw new \InvalidArgumentException('No ' . static::QUEUES_NODE . ' node found in ' . $queuesFile . '.');
        }
        return $queueClient;
    }
}
