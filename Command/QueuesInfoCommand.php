<?php

namespace ReputationVIP\Bundle\QueueClientBundle\Command;

use Psr\Log\LoggerInterface;
use ReputationVIP\Bundle\QueueClientBundle\Utils\Output;
use ReputationVIP\QueueClient\QueueClientInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class QueuesInfoCommand extends ContainerAwareCommand
{
    /**
     * @var Output
     */
    private $output;

    protected function configure()
    {
        $this
            ->setName('queue-client:queues-info')
            ->setDescription('Display queues information')
            ->addOption('no-header', null, InputOption::VALUE_NONE, 'Skip header display')
            ->addOption('alias', 'a', InputOption::VALUE_NONE, 'Display queues aliases info')
            ->addOption('count', 'c', InputOption::VALUE_NONE, 'Display count messages in queue info')
            ->addOption('priority', 'p', InputOption::VALUE_NONE, 'Display count message for each priorities')
            ->addArgument('queues', InputArgument::IS_ARRAY, 'select info from specific queues')
            ->setHelp(<<<HELP
This command display queues information.
HELP
            );
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
        $queues = $input->getArgument('queues');
        $queuesList = $queueClient->listQueues();
        if (0 === count($queues)) {
            try {
                $queues = $queuesList;
            } catch (\Exception $e) {
                $this->output->write($e->getMessage(), Output::ERROR);
                return 1;
            }
        }

        $table = new Table($output);
        $arrayRows = [];
        $queuesAliases = [];
        $priorities = [];

        if ($input->getOption('alias')) {
            $queuesAliases = $queueClient->getAliases();
        }
        if ($input->getOption('priority')) {
            $priorities = $queueClient->getPriorityHandler()->getAll();
        }
        foreach ($queues as $queue) {
            if (in_array($queue, $queuesList)) {
                $row = [$queue];
                if ($input->getOption('count')) {
                    if ($input->getOption('priority')) {
                        foreach ($priorities as $priority) {
                            $count = $queueClient->getNumberMessages($queue, $priority);
                            $row[] = $count;
                        }
                    } else {
                        $count = $queueClient->getNumberMessages($queue);
                        $row[] = $count;
                    }
                }
                if ($input->getOption('alias')) {
                    $aliases = [];
                    foreach ($queuesAliases as $keyAlias => $alias) {
                        $keys = array_keys($alias, $queue);
                        foreach ($keys as $key) {
                            $aliases[] = $keyAlias;
                        }
                    }
                    $row[] = implode(',', $aliases);
                }
                $arrayRows[] = $row;
            } else {
                $this->output->write('Queue "' . $queue . '" does not exists.', Output::WARNING);
            }
        }
        if (empty($queues)) {
            $this->output->write('No queue found.', Output::NOTICE);
            return 0;
        }
        $table->setRows($arrayRows);
        if (!$input->getOption('no-header')) {
            $headers = [];
            $headers['main'] = ['Name'];
            if ($input->getOption('count')) {
                if ($input->getOption('priority')) {
                    $headers['main'][] = new TableCell('Messages number', array('colspan' => 3));
                    $headers['sub'][] = '';
                    foreach ($priorities as $priority) {
                        $headers['sub'][] = $priority;
                    }
                } else {
                    $headers['main'][] = 'Messages number';
                }
            }
            if ($input->getOption('alias')) {
                $headers['main'][] = 'Aliases';
            }
            $table->setHeaders($headers);
        }
        $table->render();
        return 0;
    }
}
