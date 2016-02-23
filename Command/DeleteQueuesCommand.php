<?php

namespace ReputationVIP\Bundle\QueueClientBundle\Command;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use ReputationVIP\Bundle\QueueClientBundle\Configuration\QueuesConfiguration;
use ReputationVIP\Bundle\QueueClientBundle\Utils\Output;
use ReputationVIP\QueueClient\QueueClientInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Yaml\Yaml;

class DeleteQueuesCommand extends ContainerAwareCommand
{
    /**
     * @var Output $output
     */
    private $output;

    protected function configure()
    {
        $this
            ->setName('queue-client:delete-queues')
            ->setDescription('Delete queues')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'File to read')
            ->addOption('force', null, InputOption::VALUE_NONE, 'If set, the task will not ask for confirm delete')
            ->addArgument('queues', InputArgument::IS_ARRAY, 'queues to delete')
            ->setHelp(<<<HELP
This command deletes queues.

Specify file in config file:
queue_client:
    queues_file: path/to/file.yml

Or specify file with file option:
    --file=path/to/file.yml

Or list queues to delete:
    queue-client:delete-queues queue1 queue2 queue3
HELP
            );
    }

    /**
     * @param QueueClientInterface $queueClient
     * @param string $fileName
     * @return int
     */
    private function deleteFromFile($queueClient, $fileName)
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
        $this->output->write('Start delete queue.', Output::INFO);
        foreach ($processedConfiguration[QueuesConfiguration::QUEUES_NODE] as $queue) {
            $queueName = $queue[QueuesConfiguration::QUEUE_NAME_NODE];
            try {
                $queueClient->deleteQueue($queueName);
                $this->output->write('Queue ' . $queueName . ' deleted.', Output::INFO);
            } catch (\Exception $e) {
                $this->output->write($e->getMessage(), Output::WARNING);
            }
        }
        $this->output->write('End delete queue.', Output::INFO);

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
            if (!($force || $helper->ask($input, $output, new ConfirmationQuestion('Delete queues in file "' . $fileName . '"?', false)))) {

                return 0;
            }

            return $this->deleteFromFile($queueClient, $fileName);
        } else {
            $queues = $input->getArgument('queues');
            if (count($queues)) {
                if (!($force || $helper->ask($input, $output, new ConfirmationQuestion(implode("\n", $queues) . "\nDelete queues list above?"  , false)))) {

                    return 0;
                }
                foreach ($queues as $queue) {
                    try {
                        $queueClient->deleteQueue($queue);
                        $this->output->write('Queue ' . $queue . ' deleted.', Output::INFO);
                    } catch (\Exception $e) {
                        $this->output->write($e->getMessage(), Output::WARNING);
                    }
                }

                return 0;
            }
            try {
                $fileName = $this->getContainer()->getParameter('queue_client.queues_file');
                if (!($force || $helper->ask($input, $output, new ConfirmationQuestion('Delete queues in file "' . $fileName . '"?', false)))) {

                    return 0;
                }

                return $this->deleteFromFile($queueClient, $fileName);
            } catch (InvalidArgumentException $e) {
                $this->output->write('No queue_client.queues_file parameter found.', Output::CRITICAL);

                return 1;
            }
        }
    }
}
