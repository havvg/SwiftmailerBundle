<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SwiftmailerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Send Emails from the spool.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Clément JOBEILI <clement.jobeili@gmail.com>
 */
class SendEmailCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('swiftmailer:spool:send')
            ->setDescription('Sends emails from the spool')
            ->addArgument('mailer', InputArgument::OPTIONAL, 'The service of the mailer to use.', 'mailer')
            ->addArgument('transport', InputArgument::OPTIONAL, 'The service of the transport to use to send the messages.', 'swiftmailer.transport.real')
            ->addOption('message-limit', 0, InputOption::VALUE_OPTIONAL, 'The maximum number of messages to send.')
            ->addOption('time-limit', 0, InputOption::VALUE_OPTIONAL, 'The time limit for sending messages (in seconds).')
            ->addOption('recover-timeout', 0, InputOption::VALUE_OPTIONAL, 'The timeout for recovering messages that have taken too long to send (in seconds).')
            ->setHelp(<<<EOF
The <info>swiftmailer:spool:send</info> command sends all emails from the spool.

<info>php app/console swiftmailer:spool:send --message-limit=10 --time-limit=10 --recover-timeout=900</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailer     = $this->getContainer()->get($input->getArgument('mailer'));
        $transport  = $mailer->getTransport();

        if ($transport instanceof \Swift_Transport_LoadBalancedTransport) {
            foreach ($transport->getTransports() as $eachTransport) {
                $this->recoverSpool($eachTransport, $input, $output);
            }
        }

        $this->recoverSpool($transport, $input, $output);
    }

    protected function recoverSpool(\Swift_Transport $transport, InputInterface $input, OutputInterface $output)
    {
        if ($transport instanceof \Swift_Transport_SpoolTransport) {
            $spool = $transport->getSpool();
            if ($spool instanceof \Swift_ConfigurableSpool) {
                $spool->setMessageLimit($input->getOption('message-limit'));
                $spool->setTimeLimit($input->getOption('time-limit'));
            }
            if ($spool instanceof \Swift_FileSpool) {
                if (null !== $input->getOption('recover-timeout')) {
                    $spool->recover($input->getOption('recover-timeout'));
                } else {
                    $spool->recover();
                }
            }
            $sent = $spool->flushQueue($this->getContainer()->get($input->getArgument('transport')));

            $output->writeln(sprintf('sent %s emails', $sent));
        }
    }
}
