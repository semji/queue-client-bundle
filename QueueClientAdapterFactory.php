<?php

namespace ReputationVIP\Bundle\QueueClientBundle;

use Aws\Sqs\SqsClient;
use ReputationVIP\QueueClient\Adapter\AdapterInterface;
use ReputationVIP\QueueClient\Adapter\FileAdapter;
use ReputationVIP\QueueClient\Adapter\MemoryAdapter;
use ReputationVIP\QueueClient\Adapter\NullAdapter;
use ReputationVIP\QueueClient\Adapter\SQSAdapter;
use ReputationVIP\QueueClient\PriorityHandler\PriorityHandlerInterface;

class QueueClientAdapterFactory
{
    /**
     * @param array $config
     * @param PriorityHandlerInterface $priorityHandler
     * @return null|AdapterInterface
     */
    public function get($config, $priorityHandler) {
        $adapter = null;

        var_dump($config['type']);
        switch ($config['type']) {
            case 'null':
                $adapter = new NullAdapter();
                break;
            case 'file':
                $adapter = new FileAdapter($config['repository'], $priorityHandler);
                break;
            case 'sqs':
                $adapter = new SQSAdapter(SqsClient::factory(array(
                    'region' => $config['region'],
                    'version' => $config['version'],
                    'credentials' => array(
                        'key' => $config['key'],
                        'secret' => $config['secret'],
                    ),
                )), $priorityHandler);
                break;
            case 'memory':
                $adapter = new MemoryAdapter($priorityHandler);
                break;
            default:
                throw new \InvalidArgumentException('Unknown handler type : ' . $config['type']);
        }

        return $adapter;
    }
}
