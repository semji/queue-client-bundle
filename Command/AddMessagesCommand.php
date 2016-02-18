<?php

namespace ReputationVIP\Bundle\QueueClientBundle\Command;

use Psr\Log\LoggerInterface;
use ReputationVIP\Bundle\QueueClientBundle\Utils\Output;
use ReputationVIP\QueueClient\QueueClientInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class AddMessagesCommand extends ContainerAwareCommand
{
    /**
     * @var Output $output
     */
    private $output;

    protected function configure()
    {
        $this
            ->setName('queue-client:add-messages')
            ->setDescription('Add message in queue')
            ->addOption('priority', 'p', InputOption::VALUE_OPTIONAL, 'Add in queue with specific priority')
            ->addArgument('queueName', InputArgument::REQUIRED, 'queue')
            ->addArgument('messages', InputArgument::IS_ARRAY, 'messages to add')
            ->setHelp(<<<HELP
This command add messages in queue.
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
        /** @var QueueClientInterface $queueClient */
        $queueClient = $this->getContainer()->get('queue_client');

        $priority = null;
        if ($input->getOption('priority')) {
            $priority = $input->getOption('priority');
            if (!in_array($priority, $queueClient->getPriorityHandler()->getAll())) {
                throw new \InvalidArgumentException('Priority "' . $priority . '" not found.');
            }
        }

        $queueName = $input->getArgument('queueName');
        $messages = $input->getArgument('messages');

        $queueClient->addMessages($queueName, $messages, $priority);

        return 0;
    }
}
