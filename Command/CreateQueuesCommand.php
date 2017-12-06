<?php

namespace ReputationVIP\Bundle\QueueClientBundle\Command;

use InvalidArgumentException;
use ReputationVIP\Bundle\QueueClientBundle\Configuration\QueuesConfiguration;
use ReputationVIP\QueueClient\QueueClientInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class CreateQueuesCommand extends ContainerAwareCommand
{
    /** @var QueueClientInterface */
    private $queueClient;

    public function __construct(QueueClientInterface $queueClient)
    {
        parent::__construct();

        $this->queueClient = $queueClient;
    }

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
     * @param OutputInterface $output
     * @param QueueClientInterface $queueClient
     * @param string $fileName
     *
     * @return int
     */
    private function createFromFile(OutputInterface $output, QueueClientInterface $queueClient, $fileName)
    {
        try {
            $processor = new Processor();
            $configuration = new QueuesConfiguration();
            $processedConfiguration = $processor->processConfiguration($configuration, Yaml::parse(file_get_contents($fileName)));

        } catch (\Exception $e) {
            $output->writeln($e->getMessage());

            return 1;
        }
        array_walk_recursive($processedConfiguration, 'ReputationVIP\Bundle\QueueClientBundle\QueueClientFactory::resolveParameters', $this->getContainer());
        $output->writeln('Start create queue.');
        foreach ($processedConfiguration[QueuesConfiguration::QUEUES_NODE] as $queue) {
            $queueName = $queue[QueuesConfiguration::QUEUE_NAME_NODE];
            try {
                $queueClient->createQueue($queueName);
                $output->writeln('Queue ' . $queueName . ' created.');
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
            }
            foreach ($queue[QueuesConfiguration::QUEUE_ALIASES_NODE] as $alias) {
                try {
                    $queueClient->addAlias($queueName, $alias);
                    $output->writeln('Queue alias ' . $alias . ' -> ' . $queueName . ' found.');
                } catch (\Exception $e) {
                    $output->writeln($e->getMessage());
                }
            }
        }
        $output->writeln('End create queue.');

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('file')) {
            $fileName = $input->getOption('file');

            return $this->createFromFile($output, $this->queueClient, $fileName);
        } else {
            $queues = $input->getArgument('queues');
            if (count($queues)) {
                foreach ($queues as $queue) {
                    try {
                        $this->queueClient->createQueue($queue);
                        $output->writeln('Queue ' . $queue . ' created.');
                    } catch (\Exception $e) {
                        $output->writeln($e->getMessage());
                    }
                }

                return 0;
            }
            try {
                $fileName = $this->getContainer()->getParameter('queue_client.queues_file');

                return $this->createFromFile($output, $this->queueClient, $fileName);
            } catch (InvalidArgumentException $e) {
                $output->writeln('No queue_client.queues_file parameter found.');

                return 1;
            }
        }
    }
}
