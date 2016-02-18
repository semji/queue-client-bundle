<?php

namespace ReputationVIP\Bundle\QueueClientBundle\Command;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use ReputationVIP\Bundle\QueueClientBundle\Utils\Output;
use ReputationVIP\QueueClient\QueueClientInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Yaml\Yaml;

class PurgeQueuesCommand extends ContainerAwareCommand
{
    const QUEUES_NODE = 'queues';
    const QUEUE_NAME_NODE = 'name';

    /**
     * @var Output $output
     */
    private $output;

    protected function configure()
    {
        $this
            ->setName('queue-client:purge-queues')
            ->setDescription('Purge queues')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'File to read')
            ->addOption('force', null, InputOption::VALUE_NONE, 'If set, the task will not ask for confirm purge')
            ->addArgument('queues', InputArgument::IS_ARRAY, 'queues to purge')
            ->setHelp(<<<HELP
This command purge queue.

specify file in config file :
queue_client:
    queues_file: path/to/file.yml

Or specify file with file option:
    --file=path/to/file.yml

Or list queues to purge
    queue-client:purge-queues queue1 queue2 queue3
HELP
            );
    }

    /**
     * @param QueueClientInterface $queueClient
     * @param string $fileName
     * @return int
     */
    private function purgeFromFile($queueClient, $fileName) {
        try {
            $yml = Yaml::parse(file_get_contents($fileName));
            array_walk_recursive($yml, 'ReputationVIP\Bundle\QueueClientBundle\QueueClientFactory::resolveParameters', $this->getContainer());
        } catch (\Exception $e) {
            $this->output->write($e->getMessage(), Output::CRITICAL);
            return 1;
        }
        if (null === $yml) {
            $this->output->write('File ' . $fileName . ' is empty.', Output::WARNING);
            return 1;
        }
        if (array_key_exists(static::QUEUES_NODE, $yml)) {
            if (null === $yml[static::QUEUES_NODE]) {
                $this->output->write('Empty ' . static::QUEUES_NODE . ' node.', Output::CRITICAL);
                return 1;
            }
            $this->output->write('Start purge queue.', Output::INFO);
            foreach ($yml[static::QUEUES_NODE] as $queue) {
                if (empty($queue[static::QUEUE_NAME_NODE])) {
                    $this->output->write('Empty ' . static::QUEUE_NAME_NODE . ' node.', Output::CRITICAL);
                }
                $queueName = $queue[static::QUEUE_NAME_NODE];
                try {
                    $queueClient->purgeQueue($queueName);
                    $this->output->write('Queue ' . $queueName . ' purged.', Output::INFO);
                } catch (\Exception $e) {
                    $this->output->write($e->getMessage(), Output::WARNING);
                }
            }
            $this->output->write('End purge queue.', Output::INFO);
        } else {
            $this->output->write('No ' . static::QUEUES_NODE . ' node found in ' . $fileName . '.', Output::CRITICAL);
            return 1;
        }
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
            if (!($force || $helper->ask($input, $output, new ConfirmationQuestion('Purge queues in file "' . $fileName . '"?', false)))) {
                return 0;
            }
            return $this->purgeFromFile($queueClient, $fileName);
        } else {
            $queues = $input->getArgument('queues');
            if (count($queues)) {
                if (!($force || $helper->ask($input, $output, new ConfirmationQuestion(implode("\n", $queues) . "\nPurge queues list above?" , false)))) {
                    return 0;
                }
                foreach ($queues as $queue) {
                    try {
                        $queueClient->purgeQueue($queue);
                        $this->output->write('Queue ' . $queue . ' purged.', Output::INFO);
                    } catch (\Exception $e) {
                        $this->output->write($e->getMessage(), Output::WARNING);
                    }
                }
                return 0;
            }
            try {
                $fileName = $this->getContainer()->getParameter('queue_client.queues_file');
                if (!($force || $helper->ask($input, $output, new ConfirmationQuestion('Purge queues in file "' . $fileName . '"?', false)))) {
                    return 0;
                }
                return $this->purgeFromFile($queueClient, $fileName);
            } catch (InvalidArgumentException $e) {
                $this->output->write('No queue_client.queues_file parameter found.', Output::CRITICAL);
                return 1;
            }
        }
    }
}
