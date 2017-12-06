<?php

namespace ReputationVIP\Bundle\QueueClientBundle\Command;

use ReputationVIP\QueueClient\QueueClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListPrioritiesCommand extends Command
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
            ->setName('queue-client:list-priorities')
            ->setDescription('List priorities')
            ->setHelp('This command list available message priorities.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->queueClient->getPriorityHandler()->getAll() as $priority) {
            $output->writeln($priority->getName());
        }

        return 0;
    }
}
