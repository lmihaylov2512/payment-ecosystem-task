<?php

declare(strict_types=1);

namespace App\Tests\Unit\Resolver;

use App\Attribute\MapRequestHeader;
use App\Resolver\RequestHeaderValueResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RequestHeaderValueResolverTest extends TestCase
{
    private RequestHeaderValueResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new RequestHeaderValueResolver();
    }

    private function makeArgument(?MapRequestHeader $attribute): ArgumentMetadata
    {
        $argument = $this->createStub(ArgumentMetadata::class);
        $argument->method('getAttributesOfType')->willReturn($attribute ? [$attribute] : []);

        return $argument;
    }

    public function testResolvesHeaderValue(): void
    {
        $request = Request::create('/');
        $request->headers->set('Idempotency-Key', 'unique-key-123');

        $result = iterator_to_array(
            $this->resolver->resolve($request, $this->makeArgument(new MapRequestHeader('Idempotency-Key')))
        );

        $this->assertSame(['unique-key-123'], $result);
    }

    public function testThrowsBadRequestWhenHeaderIsMissing(): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('"Idempotency-Key" header is required.');

        iterator_to_array(
            $this->resolver->resolve(
                Request::create('/'),
                $this->makeArgument(new MapRequestHeader('Idempotency-Key')),
            )
        );
    }

    public function testReturnsEmptyIterableWhenNoAttributePresent(): void
    {
        $result = iterator_to_array(
            $this->resolver->resolve(Request::create('/'), $this->makeArgument(null))
        );

        $this->assertEmpty($result);
    }
}
