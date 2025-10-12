<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Tests\EventListener;

use BabDev\PagerfantaBundle\EventListener\ConvertNotValidCurrentPageToNotFoundListener;
use Pagerfanta\Exception\NotValidCurrentPageException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class ConvertNotValidCurrentPageToNotFoundListenerTest extends TestCase
{
    public function testListenerConvertsExceptionForEvent(): void
    {
        $exception = new NotValidCurrentPageException();

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->createMock(Request::class),
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        new ConvertNotValidCurrentPageToNotFoundListener()->onKernelException($event);

        self::assertInstanceOf(NotFoundHttpException::class, $event->getThrowable());
        self::assertSame($exception, $event->getThrowable()->getPrevious());
    }

    public function testListenerDoesNotConvertUnknownExceptionForEvent(): void
    {
        $exception = new \RuntimeException();

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->createMock(Request::class),
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        new ConvertNotValidCurrentPageToNotFoundListener()->onKernelException($event);

        self::assertSame($exception, $event->getThrowable());
    }
}
