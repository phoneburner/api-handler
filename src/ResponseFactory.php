<?php

declare(strict_types=1);

namespace PhoneBurner\ApiHandler;

use Psr\Http\Message\ResponseInterface;

interface ResponseFactory
{
    public function make(TransformableResource|null $resource = null, int $code = 200): ResponseInterface;
}
