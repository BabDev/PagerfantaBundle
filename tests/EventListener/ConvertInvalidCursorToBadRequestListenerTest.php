<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\EventListener;

use BabDev\PagerfantaBundle\EventListener\ConvertInvalidCursorToBadRequestListener;
use Pagerfanta\Exception\InvalidCursorException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ConvertInvalidCursorToBadRequestListenerTest extends TestCase
{
    public function testListenerConvertsExceptionForEvent(): void
    {
        $exception = new InvalidCursorException('The cursor signature is not valid.');

        $event = new ExceptionEvent(
            self::createStub(HttpKernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        new ConvertInvalidCursorToBadRequestListener()->onKernelException($event);

        self::assertInstanceOf(BadRequestHttpException::class, $event->getThrowable());
        self::assertSame($exception, $event->getThrowable()->getPrevious());
    }

    public function testListenerDoesNotConvertUnknownExceptionForEvent(): void
    {
        $exception = new \RuntimeException();

        $event = new ExceptionEvent(
            self::createStub(HttpKernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        new ConvertInvalidCursorToBadRequestListener()->onKernelException($event);

        self::assertSame($exception, $event->getThrowable());
    }
}
