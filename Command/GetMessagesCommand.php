<?php

namespace ReputationVIP\Bundle\QueueClientBundle\Command;

use ReputationVIP\QueueClient\QueueClientInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GetMessagesCommand extends ContainerAwareCommand
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
            ->setName('queue-client:get-messages')
            ->setDescription('Get messages from queue')
            ->addOption('number-messages', 'c', InputOption::VALUE_OPTIONAL, 'Number of messages to be retrieved')
            ->addOption('pop', null, InputOption::VALUE_NONE, 'If set, the task will remove ask messages from queue')
            ->addOption('priority', 'p', InputOption::VALUE_OPTIONAL, 'Get messages from specific priority')
            ->addArgument('queueName', InputArgument::REQUIRED, 'queue')
            ->setHelp('This command get messages from queue');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queueName = $input->getArgument('queueName');
        $numberMessages = $input->getOption('number-messages') ?: 1;
        $priority = null;
        if ($input->getOption('priority')) {
            $priority = $input->getOption('priority');
            if (!in_array($priority, $this->queueClient->getPriorityHandler()->getAll())) {
                throw new \InvalidArgumentException('Priority "' . $priority . '" not found.');
            }
        }
        $messages = $this->queueClient->getMessages($queueName, $numberMessages, $priority);
        foreach ($messages as $message) {
            if (is_array($message['Body'])) {
                $output->writeln(json_encode($message['Body']));
            } else {
                $output->writeln($message['Body']);
            }
        }

        if ($input->getOption('pop')) {
            $this->queueClient->deleteMessages($queueName, $messages);
        }

        return 0;
    }
}
