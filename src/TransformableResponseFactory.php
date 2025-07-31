<?php

declare(strict_types=1);

namespace PhoneBurner\ApiHandler;

use Psr\Http\Message\ResponseInterface;

class TransformableResponseFactory implements ResponseFactory
{
    public function __construct(
        private readonly ResponseFactory $realizing_factory,
    ) {
    }

    public function make(TransformableResource|null $resource = null, int $code = 200): ResponseInterface
    {
        if ($resource === null) {
            return $this->realizing_factory->make(null, $code);
        }

        return new TransformableResponse($resource, $this->realizing_factory, $code);
    }
}
