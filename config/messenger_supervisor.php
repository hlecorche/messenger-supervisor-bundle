<?php

declare(strict_types=1);

/*
 * This file is part of the EcommitMessengerSupervisorBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Ecommit\MessengerSupervisorBundle\Command\ManageCommand;
use Ecommit\MessengerSupervisorBundle\EventListener\WorkerMessageFailedEventListener;
use Ecommit\MessengerSupervisorBundle\Mailer\ErrorEmailBuilder;
use Ecommit\MessengerSupervisorBundle\Supervisor\Supervisor;
use Ecommit\MessengerSupervisorBundle\Supervisor\SupervisorApiFactory;
use Psr\Log\LoggerInterface;

return static function (ContainerConfigurator $container): void {
    $container->parameters()
        ->set('ecommit_messenger_supervisor.error_email_builder_service', 'ecommit_messenger_supervisor.error_email_builder')
    ;

    $container->services()

        ->set('ecommit_messenger_supervisor.supervisor_api', \Supervisor\Supervisor::class)
        ->factory([SupervisorApiFactory::class, 'createSupervisor'])
        ->args([
            param('ecommit_messenger_supervisor.supervisor'),
        ])
        ->alias(\Supervisor\Supervisor::class, 'ecommit_messenger_supervisor.supervisor_api')

        ->set('ecommit_messenger_supervisor.supervisor', Supervisor::class)
        ->args([
            service('ecommit_messenger_supervisor.supervisor_api'),
            param('ecommit_messenger_supervisor.transports'),
        ])
        ->alias(Supervisor::class, 'ecommit_messenger_supervisor.supervisor')

        ->set('ecommit_messenger_supervisor.event_listener.worker_message_failed', WorkerMessageFailedEventListener::class)
        ->args([
            service('ecommit_messenger_supervisor.supervisor'),
            null,
            service(LoggerInterface::class)->nullOnInvalid(),
            service('mailer'),
            param('ecommit_messenger_supervisor.mailer'),
        ])
        ->tag('kernel.event_listener', ['event' => 'Symfony\Component\Messenger\Event\WorkerMessageFailedEvent', 'method' => 'onFailure', 'priority' => '%ecommit_messenger_supervisor.failure_event_priority%'])

        ->set('ecommit_messenger_supervisor.error_email_builder', ErrorEmailBuilder::class)
        ->args([
            service('twig'),
        ])

        ->set(ManageCommand::class, ManageCommand::class)
        ->private()
        ->args([
            service('ecommit_messenger_supervisor.supervisor'),
        ])
        ->tag('console.command')
    ;
};
