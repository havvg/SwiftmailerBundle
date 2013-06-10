<?php

namespace Symfony\Bundle\SwiftmailerBundle\Tests\Command;

use Symfony\Bundle\SwiftmailerBundle\Command\SendEmailCommand;
use Symfony\Bundle\SwiftmailerBundle\Tests\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;

class SendEmailCommandTest extends TestCase
{
    public function testRecoverSpoolTransport()
    {
        $realTransport = $this->getMock('Swift_Transport');

        $spool = $this->getMock('Swift_Spool');
        $spool
            ->expects($this->once())
            ->method('flushQueue')
            ->with($realTransport)
            ->will($this->returnValue(5))
        ;

        $spoolTransport = new \Swift_Transport_SpoolTransport(new \Swift_Events_SimpleEventDispatcher(), $spool);

        $mailer = new \Swift_Mailer($spoolTransport);

        $container = new Container();
        $container->set('mailer', $mailer);
        $container->set('swiftmailer.transport.real', $realTransport);

        $command = new SendEmailCommand();
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $tester->execute(array());

        $this->assertEquals("sent 5 emails\n", $tester->getDisplay());
    }

    public function testRecoverLoadbalancedTransportWithSpool()
    {
        $realTransport = $this->getMock('Swift_Transport');

        $spool = $this->getMock('Swift_Spool');
        $spool
            ->expects($this->once())
            ->method('flushQueue')
            ->with($realTransport)
            ->will($this->returnValue(7))
        ;

        $spoolTransport = new \Swift_Transport_SpoolTransport(new \Swift_Events_SimpleEventDispatcher(), $spool);

        $loadBalancedTransport = new \Swift_Transport_LoadBalancedTransport();
        $loadBalancedTransport->setTransports(array($spoolTransport));

        $mailer = new \Swift_Mailer($loadBalancedTransport);

        $container = new Container();
        $container->set('mailer', $mailer);
        $container->set('swiftmailer.transport.real', $realTransport);

        $command = new SendEmailCommand();
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $tester->execute(array());

        $this->assertEquals("sent 7 emails\n", $tester->getDisplay());
    }
}
