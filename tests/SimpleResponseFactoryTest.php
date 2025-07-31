<?php

declare(strict_types=1);

namespace PhoneBurner\Tests\ApiHandler;

use PhoneBurner\ApiHandler\SimpleResponseFactory;
use PhoneBurner\ApiHandler\TransformableResource;
use PhoneBurner\ApiHandler\Transformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

final class SimpleResponseFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ResponseFactoryInterface>
     */
    private ObjectProphecy $response_factory;

    /**
     * @var ObjectProphecy<StreamFactoryInterface>
     */
    private ObjectProphecy $stream_factory;

    private SimpleResponseFactory $sut;

    protected function setUp(): void
    {
        $this->response_factory = $this->prophesize(ResponseFactoryInterface::class);
        $this->stream_factory = $this->prophesize(StreamFactoryInterface::class);

        $this->sut = new SimpleResponseFactory(
            $this->response_factory->reveal(),
            $this->stream_factory->reveal(),
        );
    }

    #[Test]
    #[TestWith([200])]
    #[TestWith([201])]
    #[TestWith([202])]
    public function makeAllowsVariedStatusCodes(int $status): void
    {
        $resource = new \stdClass();
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $transformer = $this->prophesize(Transformer::class);

        $transformer->transform($resource, $request)->willReturn("content");

        $response = $this->prophesize(ResponseInterface::class);
        $this->response_factory->createResponse($status)->willReturn($response->reveal());

        $stream = $this->prophesize(StreamInterface::class);
        $this->stream_factory->createStream("content")->willReturn($stream->reveal());

        $with_response = $this->prophesize(ResponseInterface::class)->reveal();
        $response->withBody($stream->reveal())->willReturn($with_response);

        $transformable_resource = new TransformableResource($resource, $request, $transformer->reveal());

        self::assertSame(
            $with_response,
            $this->sut->make($transformable_resource, $status),
        );
    }

    #[Test]
    #[TestWith([200])]
    #[TestWith([201])]
    #[TestWith([202])]
    public function makeAllowsNullTransformableResource(int $status): void
    {
        $response = $this->prophesize(ResponseInterface::class);
        $this->response_factory->createResponse($status)->willReturn($response->reveal());

        self::assertSame(
            $response->reveal(),
            $this->sut->make(null, $status),
        );
    }

    #[Test]
    #[DataProvider('provideReturns')]
    public function makeReturnsResponseWithExpectedBody(mixed $value, string $content): void
    {
        $resource = new \stdClass();
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $transformer = $this->prophesize(Transformer::class);

        $transformer->transform($resource, $request)->willReturn($value);

        $response = $this->prophesize(ResponseInterface::class);
        $this->response_factory->createResponse(200)->willReturn($response->reveal());

        $stream = $this->prophesize(StreamInterface::class);
        $this->stream_factory->createStream($content)->willReturn($stream->reveal());

        $with_response = $this->prophesize(ResponseInterface::class)->reveal();
        $response->withBody($stream->reveal())->willReturn($with_response);

        $transformable_resource = new TransformableResource($resource, $request, $transformer->reveal());

        self::assertSame(
            $with_response,
            $this->sut->make($transformable_resource, 200),
        );
    }

    #[Test]
    public function makeAllowsNull(): void
    {
        $resource = new \stdClass();
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $transformer = $this->prophesize(Transformer::class);

        $transformer->transform($resource, $request)->willReturn(null);

        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->response_factory->createResponse(200)->willReturn($response);

        $transformable_resource = new TransformableResource($resource, $request, $transformer->reveal());

        self::assertSame(
            $response,
            $this->sut->make($transformable_resource, 200),
        );
    }

    #[Test]
    public function makeAllowsResource(): void
    {
        $resource = new \stdClass();
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $transformer = $this->prophesize(Transformer::class);

        $stream_resource = \fopen('php://temp', 'r+');

        $transformer->transform($resource, $request)->willReturn($stream_resource);

        $response = $this->prophesize(ResponseInterface::class);
        $this->response_factory->createResponse(200)->willReturn($response->reveal());

        $stream = $this->prophesize(StreamInterface::class);
        $this->stream_factory->createStreamFromResource($stream_resource)->willReturn($stream->reveal());

        $with_response = $this->prophesize(ResponseInterface::class)->reveal();
        $response->withBody($stream->reveal())->willReturn($with_response);

        $transformable_resource = new TransformableResource($resource, $request, $transformer->reveal());

        self::assertSame(
            $with_response,
            $this->sut->make($transformable_resource, 200),
        );
    }

    public static function provideReturns(): \Generator
    {
        yield 'string' => ['an api response', 'an api response'];

        foreach ([true, false] as $value) {
            yield 'bool: ' . ($value ? 'true' : 'false') => [$value, \json_encode($value, \JSON_THROW_ON_ERROR)];
        }

        $array = ['an' => 'api', 'response' => 'array'];
        yield 'array' => [$array, \json_encode($array, \JSON_THROW_ON_ERROR)];

        $object = new \stdClass();
        $object->an = 'api';
        $object->response = 'object';

        yield 'object' => [$object, \json_encode($object, \JSON_THROW_ON_ERROR)];
    }
}
