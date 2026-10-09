<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\EventListener;

use Pagerfanta\Exception\InvalidCursorException;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ConvertInvalidCursorToBadRequestListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof InvalidCursorException) {
            $event->setThrowable(new BadRequestHttpException('Invalid Cursor', $throwable));
        }
    }
}
