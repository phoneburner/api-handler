<?php

declare(strict_types=1);

namespace PhoneBurner\Tests\ApiHandler;

use PhoneBurner\ApiHandler\ResponseFactory;
use PhoneBurner\ApiHandler\TransformableResource;
use PhoneBurner\ApiHandler\TransformableResponse;
use PhoneBurner\ApiHandler\Transformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

class TransformableResponseTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ResponseFactory>
     */
    private ObjectProphecy $factory;

    /**
     * @var ObjectProphecy<ResponseInterface>
     */
    private ObjectProphecy $realized_response;

    private TransformableResource $transformable_resource;

    protected function setUp(): void
    {
        $this->realized_response = $this->prophesize(ResponseInterface::class);

        $resource = new \stdClass();
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $transformer = $this->prophesize(Transformer::class)->reveal();

        $this->transformable_resource = new TransformableResource(
            $resource,
            $request,
            $transformer,
        );

        $this->factory = $this->prophesize(ResponseFactory::class);
        $this->factory->make($this->transformable_resource, 200)
            ->willReturn($this->realized_response->reveal());
    }

    #[Test]
    public function transformable_resource_is_accessible(): void
    {
        $sut = new TransformableResponse(
            $this->transformable_resource,
            $this->factory->reveal(),
        );
        self::assertSame($this->transformable_resource, $sut->transformable_resource);
    }

    #[Test]
    public function withTransformableResource_replaces_TransformableResource(): void
    {
        $resource = new \stdClass();
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $transformer = $this->prophesize(Transformer::class)->reveal();

        $other = new TransformableResource(
            $resource,
            $request,
            $transformer,
        );

        $sut = new TransformableResponse(
            $this->transformable_resource,
            $this->factory->reveal(),
        );

        $other_response = $this->prophesize(ResponseInterface::class);
        $other_response->getReasonPhrase()->willReturn('test');
        $this->factory->make($other, 200)
            ->willReturn($other_response->reveal());

        $mutated = $sut->withTransformableResource($other);

        self::assertNotSame($sut, $mutated);
        self::assertSame($other, $mutated->transformable_resource);
        self::assertSame('test', $mutated->getReasonPhrase());

        self::assertSame($this->transformable_resource, $sut->transformable_resource);
    }

    #[Test]
    #[DataProvider('provideWithMethods')]
    public function withMethods_realize_Response_once_and_return(string $method, array $args): void
    {
        $this->factory->make($this->transformable_resource, 200)->shouldBeCalledOnce();

        $sut = new TransformableResponse(
            $this->transformable_resource,
            $this->factory->reveal(),
        );

        $mutated_response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->realized_response->$method(...$args)->willReturn($mutated_response);

        // make should only be called once
        self::assertSame($mutated_response, $sut->$method(...$args)->getWrapped());
        self::assertSame($mutated_response, $sut->$method(...$args)->getWrapped());
    }

    #[Test]
    #[DataProvider('provideGetMethods')]
    public function getMethods_realize_Response_once_and_pass_response(string $method, array $args, mixed $return): void
    {
        $this->factory->make($this->transformable_resource, 200)->shouldBeCalledOnce();

        $sut = new TransformableResponse(
            $this->transformable_resource,
            $this->factory->reveal(),
        );

        $this->realized_response->$method(...$args)->willReturn($return);

        // make should only be called once
        self::assertSame($return, $sut->$method(...$args));
        self::assertSame($return, $sut->$method(...$args));
    }

    #[Test]
    public function getStatusCode_does_not_realize_Response(): void
    {
        $this->factory->make(Argument::cetera(), 200)
            ->shouldNotBeCalled();

        $sut = new TransformableResponse($this->transformable_resource, $this->factory->reveal());
        self::assertSame(200, $sut->getStatusCode());
    }

    public static function provideWithMethods(): \Generator
    {
        yield "withStatus(200)" => ['withStatus', [200, '']];
        yield "withProtocolVersion('1.1')" => ['withProtocolVersion', ['1.1']];

        yield "withHeader('test', 'line one')" => ['withHeader', ['test', 'line one']];
        yield "withAddedHeader('test', 'line one')" => ['withAddedHeader', ['test', 'line one']];
        yield "withoutHeader('test')" => ['withoutHeader', ['test']];

        $stream = self::createStub(StreamInterface::class);

        yield "withBody(StreamInterface)" => ['withBody', [$stream]];
    }

    public static function provideGetMethods(): \Generator
    {
        yield "getReasonPhrase() => ''" => ['getReasonPhrase', [], ''];
        yield "getReasonPhrase() => 'OK'" => ['getReasonPhrase', [], 'OK'];

        yield "getProtocolVersion() => '1.1'" => ['getProtocolVersion', [], '1.1'];
        yield "getProtocolVersion() => '1.0'" => ['getProtocolVersion', [], '1.0'];

        $header = [
            'line one',
            'line two',
        ];

        $headers = [
            'test' => $header,
        ];

        yield "getHeaders()" => ['getHeaders', [], $headers];
        yield "getHeader('test)" => ['getHeader', ['test'], $header];

        yield "hasHeader('test) => true" => ['hasHeader', ['test'], true];
        yield "hasHeader('test) => false" => ['hasHeader', ['test'], false];

        yield "getHeaderLine('test)" => ['getHeaderLine', ['test'], 'line one, line two'];

        $stream = self::createStub(StreamInterface::class);

        yield "getBody()" => ['getBody', [], $stream];
    }
}
