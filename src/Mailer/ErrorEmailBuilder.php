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

namespace Ecommit\MessengerSupervisorBundle\Mailer;

use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Twig\Environment;

class ErrorEmailBuilder implements ErrorEmailBuilderInterface
{
    /**
     * @var Environment
     */
    protected $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function getBody(WorkerMessageFailedEvent $event, array $transportInfos, array $mailerParameters, bool $stop): string
    {
        return $this->twig->render(
            $this->getTemplate($event, $transportInfos, $mailerParameters, $stop),
            $this->getContext($event, $transportInfos, $mailerParameters, $stop)
        );
    }

    protected function getTemplate(WorkerMessageFailedEvent $event, array $transportInfos, array $mailerParameters, bool $stop): string
    {
        return '@EcommitMessengerSupervisor/Email/failure.html.twig';
    }

    protected function getContext(WorkerMessageFailedEvent $event, array $transportInfos, array $mailerParameters, bool $stop): array
    {
        return [
            'event' => $event,
            'program' => $transportInfos['program'],
            'transport_infos' => $transportInfos,
            'stop_program' => $stop,
            'throwable_message' => $this->getThrowableMessage($event->getThrowable()),
        ];
    }

    protected function getThrowableMessage(\Throwable $throwable): string
    {
        return sprintf('%s: %s at %s line %s', \get_class($throwable), $throwable->getMessage(), $throwable->getFile(), $throwable->getLine());
    }

    public function getSubject(WorkerMessageFailedEvent $event, array $transportInfos, array $mailerParameters, bool $stop): string
    {
        $subject = $mailerParameters['subject'];
        $subject = str_replace('<program>', $transportInfos['program'], $subject);

        return $subject;
    }
}
