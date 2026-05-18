<?php

declare(strict_types=1);

namespace App\Resolver;

use App\Attribute\MapRequestHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[ValueResolver('map_request_header')]
class RequestHeaderValueResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $attribute = $argument->getAttributesOfType(MapRequestHeader::class)[0] ?? null;

        if ($attribute === null) {
            return [];
        }

        $value = $request->headers->get($attribute->name);

        if (empty($value)) {
            throw new BadRequestHttpException(sprintf('"%s" header is required.', $attribute->name));
        }

        yield $value;
    }
}
