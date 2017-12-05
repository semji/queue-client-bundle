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
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Yaml;

class PurgeQueuesCommand extends ContainerAwareCommand
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
            ->setName('queue-client:purge-queues')
            ->setDescription('Purge queues')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'File to read')
            ->addOption('force', null, InputOption::VALUE_NONE, 'If set, the task will not ask for confirm purge')
            ->addOption('priority', 'p', InputOption::VALUE_OPTIONAL, 'Get messages from specific priority')
            ->addArgument('queues', InputArgument::IS_ARRAY, 'queues to purge')
            ->setHelp(<<<HELP
This command purges queues.

Specify file in config file:
queue_client:
    queues_file: path/to/file.yml

Or specify file with file option:
    --file=path/to/file.yml

Or list queues to purge:
    queue-client:purge-queues queue1 queue2 queue3
HELP
            );
    }

    /**
     * @param OutputInterface $output
     * @param $fileName
     * @param $priority
     * @return int
     */
    private function purgeFromFile(OutputInterface $output, $fileName, $priority)
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
        $output->writeln('Start purge queue.');
        foreach ($processedConfiguration[QueuesConfiguration::QUEUES_NODE] as $queue) {
            $queueName = $queue[QueuesConfiguration::QUEUE_NAME_NODE];
            try {
                $this->queueClient->purgeQueue($queueName, $priority);
                $output->writeln('Queue ' . $queueName . ' purged.');
            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
            }
        }
        $output->writeln('End purge queue.');

        return 0;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $force = $input->getOption('force') ? true : false;

        $priority = null;
        if ($input->getOption('priority')) {
            $priority = $input->getOption('priority');
            if (!in_array($priority, $this->queueClient->getPriorityHandler()->getAll())) {
                throw new \InvalidArgumentException('Priority "' . $priority . '" not found.');
            }
        }
        if ($input->getOption('file')) {
            $fileName = $input->getOption('file');
            if (!($force || $helper->ask($input, $output, new ConfirmationQuestion('Purge queues in file "' . $fileName . '"?', false)))) {

                return 0;
            }

            return $this->purgeFromFile($output, $fileName, $priority);
        } else {
            $queues = $input->getArgument('queues');
            if (count($queues)) {
                if (!($force || $helper->ask($input, $output, new ConfirmationQuestion(implode("\n", $queues) . "\nPurge queues list above?" , false)))) {

                    return 0;
                }
                foreach ($queues as $queue) {
                    try {
                        $this->queueClient->purgeQueue($queue, $priority);
                        $output->writeln('Queue ' . $queue . ' purged.');
                    } catch (\Exception $e) {
                        $output->writeln($e->getMessage());
                    }
                }

                return 0;
            }
            try {
                $fileName = $this->getContainer()->getParameter('queue_client.queues_file');
                if (!($force || $helper->ask($input, $output, new ConfirmationQuestion('Purge queues in file "' . $fileName . '"?', false)))) {

                    return 0;
                }

                return $this->purgeFromFile($output, $fileName, $priority);
            } catch (InvalidArgumentException $e) {
                $output->writeln('No queue_client.queues_file parameter found.');

                return 1;
            }
        }
    }
}
