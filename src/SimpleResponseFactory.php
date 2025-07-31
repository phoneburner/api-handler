<?php

declare(strict_types=1);

namespace PhoneBurner\ApiHandler;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class SimpleResponseFactory implements ResponseFactory
{
    public function __construct(
        private readonly ResponseFactoryInterface $response_factory,
        private readonly StreamFactoryInterface $stream_factory,
    ) {
    }

    public function make(TransformableResource|null $resource = null, int $code = 200): ResponseInterface
    {
        if ($resource === null) {
            return $this->response_factory->createResponse($code);
        }

        $content = $resource->getContent();
        $response = $this->response_factory->createResponse($code);

        return $content === null ? $response : $response->withBody(match (true) {
            \is_resource($content) => $this->stream_factory->createStreamFromResource($content),
            \is_string($content) => $this->stream_factory->createStream($content),
            default => $this->stream_factory->createStream(\json_encode($content, \JSON_THROW_ON_ERROR) ?: '')
        });
    }
}
