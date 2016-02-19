<?php

namespace ReputationVIP\Bundle\QueueClientBundle\Command;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use ReputationVIP\Bundle\QueueClientBundle\Utils\Output;
use ReputationVIP\Bundle\QueueClientBundle\Configuration\QueuesConfiguration;
use ReputationVIP\QueueClient\QueueClientInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Yaml\Yaml;

class CreateQueuesCommand extends ContainerAwareCommand
{
    /**
     * @var Output $output
     */
    private $output;

    protected function configure()
    {
        $this
            ->setName('queue-client:create-queues')
            ->setDescription('Create queues')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'File to read')
            ->addArgument('queues', InputArgument::IS_ARRAY, 'queues to create')
            ->setHelp(<<<HELP
This command creates queues.

Specify file in config file:
queue_client:
    queues_file: path/to/file.yml

Or specify file with file option:
    --file=path/to/file.yml

Or list queues to create:
    queue-client:create-queues queue1 queue2 queue3
HELP
            );
    }

    /**
     * @param QueueClientInterface $queueClient
     * @param string $fileName
     * @return int
     */
    private function createFromFile($queueClient, $fileName)
    {
        try {
            $processor = new Processor();
            $configuration = new QueuesConfiguration();
            $processedConfiguration = $processor->processConfiguration($configuration, Yaml::parse(file_get_contents($fileName)));

        } catch (\Exception $e) {
            $this->output->write($e->getMessage(), Output::CRITICAL);

            return 1;
        }
        array_walk_recursive($processedConfiguration, 'ReputationVIP\Bundle\QueueClientBundle\QueueClientFactory::resolveParameters', $this->getContainer());
        $this->output->write('Start create queue.', Output::INFO);
        foreach ($processedConfiguration[QueuesConfiguration::QUEUES_NODE] as $queue) {
            $queueName = $queue[QueuesConfiguration::QUEUE_NAME_NODE];
            try {
                $queueClient->createQueue($queueName);
                $this->output->write('Queue ' . $queueName . ' created.', Output::INFO);
            } catch (\Exception $e) {
                $this->output->write($e->getMessage(), Output::WARNING);
            }
            foreach ($queue[QueuesConfiguration::QUEUE_ALIASES_NODE] as $alias) {
                try {
                    $queueClient->addAlias($queueName, $alias);
                    $this->output->write('Queue alias ' . $alias . ' -> ' . $queueName . ' found.', Output::INFO);
                } catch (\Exception $e) {
                    $this->output->write($e->getMessage(), Output::WARNING);
                }
            }
        }
        $this->output->write('End create queue.', Output::INFO);

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            /** @var LoggerInterface $logger */
            $logger = $this->getContainer()->get('logger');
        } catch (ServiceNotFoundException $e) {
            $logger = null;
        }
        $this->output = new Output($logger, $output);
        try {
            /** @var QueueClientInterface $queueClient */
            $queueClient = $this->getContainer()->get('queue_client');
        } catch (ServiceNotFoundException $e) {
            $this->output->write('No queue client service found.', Output::CRITICAL);

            return 1;
        }
        if ($input->getOption('file')) {
            $fileName = $input->getOption('file');

            return $this->createFromFile($queueClient, $fileName);
        } else {
            $queues = $input->getArgument('queues');
            if (count($queues)) {
                foreach ($queues as $queue) {
                    try {
                        $queueClient->createQueue($queue);
                        $this->output->write('Queue ' . $queue . ' created.', Output::INFO);
                    } catch (\Exception $e) {
                        $this->output->write($e->getMessage(), Output::WARNING);
                    }
                }

                return 0;
            }
            try {
                $fileName = $this->getContainer()->getParameter('queue_client.queues_file');

                return $this->createFromFile($queueClient, $fileName);
            } catch (InvalidArgumentException $e) {
                $this->output->write('No queue_client.queues_file parameter found.', Output::CRITICAL);

                return 1;
            }
        }
    }
}
