<?php

namespace ReputationVIP\Bundle\QueueClientBundle\Utils;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Output
{
    const ERROR = 'error';
    const CRITICAL = 'critical';
    const INFO = 'info';
    const NOTICE = 'notice';
    const WARNING = 'warning';

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger, OutputInterface $output)
    {
        $this->logger = $logger;
        $this->output = $output;
    }

    public function write($msg, $level) {
        if (null !== $this->logger) {
            switch ($level) {
                case self::ERROR :
                    $this->logger->error($msg);
                    break;
                case self::CRITICAL :
                    $this->logger->critical($msg);
                    break;
                case self::INFO :
                    $this->logger->info($msg);
                    break;
                case self::NOTICE :
                    $this->logger->notice($msg);
                    break;
                case self::WARNING :
                    $this->logger->warning($msg);
                    break;
                default :
                    $this->logger->debug($msg);
            }
        } else {
            switch ($level) {
                case self::ERROR :
                    $this->output->writeln('<error>' . $msg . '</error>');
                    break;
                case self::CRITICAL :
                    $this->output->writeln('<critical>' . $msg . '</critical>');
                    break;
                case self::INFO :
                    $this->output->writeln('<info>' . $msg . '</info>');
                    break;
                case self::NOTICE :
                    $this->output->writeln('<notice>' . $msg . '</notice>');
                    break;
                case self::WARNING :
                    $this->output->writeln('<warning>' . $msg . '</warning>');
                    break;
                default :
                    $this->output->writeln($msg);
            }
        }
    }
}
