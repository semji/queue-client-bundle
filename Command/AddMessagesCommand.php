<?php

namespace ReputationVIP\Bundle\QueueClientBundle\Command;

use ReputationVIP\QueueClient\QueueClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddMessagesCommand extends Command
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
            ->setName('queue-client:add-messages')
            ->setDescription('Add message in queue')
            ->addOption('priority', 'p', InputOption::VALUE_OPTIONAL, 'Add in queue with specific priority')
            ->addArgument('queueName', InputArgument::REQUIRED, 'queue')
            ->addArgument('messages', InputArgument::IS_ARRAY, 'messages to add')
            ->setHelp('This command add messages in queue.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $priority = null;
        if ($input->getOption('priority')) {
            $priority = $input->getOption('priority');
            if (!in_array($priority, $this->queueClient->getPriorityHandler()->getAll())) {
                throw new \InvalidArgumentException('Priority "' . $priority . '" not found.');
            }
        }

        $queueName = $input->getArgument('queueName');
        $messages = $input->getArgument('messages');

        $this->queueClient->addMessages($queueName, $messages, $priority);

        return 0;
    }
}
