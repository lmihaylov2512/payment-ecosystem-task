<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\JsonExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class JsonExceptionSubscriberTest extends TestCase
{
    private JsonExceptionSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new JsonExceptionSubscriber();
    }

    private function makeEvent(\Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create('/'),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }

    public function testHandlesHttpExceptionWithCorrectStatusCode(): void
    {
        $event = $this->makeEvent(new NotFoundHttpException('Resource not found'));
        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $body     = json_decode($response->getContent(), true);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(404, $body['status']);
        $this->assertSame('Resource not found', $body['error']);
    }

    public function testHandlesGenericExceptionAs500(): void
    {
        $event = $this->makeEvent(new \RuntimeException('Something broke'));
        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $body     = json_decode($response->getContent(), true);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(500, $body['status']);
        $this->assertSame('Something broke', $body['error']);
    }

    public function testHandlesValidationExceptionWithStructuredErrors(): void
    {
        $violations = new ConstraintViolationList([
            new ConstraintViolation('user_id is required.', null, [], null, 'userId', null),
            new ConstraintViolation('customer_email must be a valid email address.', null, [], null, 'customerEmail', null),
        ]);
        $previous = new ValidationFailedException(null, $violations);
        $wrapper  = new UnprocessableEntityHttpException('Validation failed', $previous);

        $event = $this->makeEvent($wrapper);
        $this->subscriber->onKernelException($event);

        $response = $event->getResponse();
        $body     = json_decode($response->getContent(), true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertArrayHasKey('errors', $body);
        $this->assertSame('user_id is required.', $body['errors']['userId']);
        $this->assertSame('customer_email must be a valid email address.', $body['errors']['customerEmail']);
    }

    public function testSubscribesToKernelExceptionEvent(): void
    {
        $events = JsonExceptionSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey('kernel.exception', $events);
    }
}
