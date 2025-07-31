<?php

declare(strict_types=1);

namespace PhoneBurner\Tests\ApiHandler;

use PhoneBurner\ApiHandler\ResponseFactory;
use PhoneBurner\ApiHandler\TransformableResource;
use PhoneBurner\ApiHandler\TransformableResponseFactory;
use PhoneBurner\ApiHandler\Transformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TransformableResponseFactoryTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    #[TestWith([200])]
    #[TestWith([201])]
    public function makeReturnsTransformableResponseConfiguredWithRealizingFactory(int $status): void
    {
        $resource = new \stdClass();
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $transformer = $this->prophesize(Transformer::class)->reveal();

        $realizing_factory = $this->prophesize(ResponseFactory::class);

        $transformable_resource = new TransformableResource($resource, $request, $transformer);

        $response = $this->prophesize(ResponseInterface::class);
        $realizing_factory->make($transformable_resource, $status)
            ->willReturn($response->reveal());

        $sut = new TransformableResponseFactory($realizing_factory->reveal());

        $transformable_response = $sut->make($transformable_resource, $status);

        $response->getReasonPhrase()->willReturn('test');

        self::assertSame($status, $transformable_response->getStatusCode());

        self::assertSame(
            'test',
            $transformable_response->getReasonPhrase(),
        );
    }

    #[Test]
    public function makeReturnsEmptyResponseWithoutTransformableResponse(): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $realizing_factory = $this->prophesize(ResponseFactory::class);
        $realizing_factory->make(null, 202)->willReturn($response->reveal());

        $sut = new TransformableResponseFactory($realizing_factory->reveal());

        self::assertSame(
            $response->reveal(),
            $sut->make(null, 202),
        );
    }
}
