<?php

declare(strict_types=1);

namespace PhoneBurner\Tests\ApiHandler;

use PhoneBurner\ApiHandler\Hydrator;
use PhoneBurner\ApiHandler\Resolver;
use PhoneBurner\ApiHandler\ResponseFactory;
use PhoneBurner\ApiHandler\TransformableResource;
use PhoneBurner\ApiHandler\Transformer;
use PhoneBurner\ApiHandler\UpdateHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class UpdateHandlerTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<ResponseFactory>
     */
    private ObjectProphecy $factory;

    /**
     * @var ObjectProphecy<Hydrator>
     */
    private ObjectProphecy $hydrator;

    /**
     * @var ObjectProphecy<Resolver>
     */
    private ObjectProphecy $resolver;

    /**
     * @var ObjectProphecy<Transformer>
     */
    private ObjectProphecy $transformer;

    private UpdateHandler $sut;

    protected function setUp(): void
    {
        $this->resolver = $this->prophesize(Resolver::class);
        $this->hydrator = $this->prophesize(Hydrator::class);
        $this->transformer = $this->prophesize(Transformer::class);

        $this->sut = new UpdateHandler(
            $this->resolver->reveal(),
            $this->hydrator->reveal(),
            $this->transformer->reveal(),
        );

        $this->factory = $this->prophesize(ResponseFactory::class);
    }

    #[Test]
    public function handleResolvesResourceAndReturnsUpdatedResource(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $resource = new stdClass();
        $this->resolver->resolve($request)->willReturn($resource);

        $updated = new stdClass();
        $this->hydrator->update($request, $resource)->willReturn($updated)->shouldBeCalledOnce();

        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->factory->make(new TransformableResource($updated, $request, $this->transformer->reveal()), 200)
            ->willReturn($response);

        $this->sut->setResponseFactory($this->factory->reveal());

        self::assertSame($response, $this->sut->handle($request));
    }

    #[Test]
    public function handleResolvesResourceAndAllowsNullUpdatedResource(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $resource = new stdClass();
        $this->resolver->resolve($request)->willReturn($resource);

        $this->hydrator->update($request, $resource)->willReturn(null)->shouldBeCalledOnce();

        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->factory->make(null, 204)
            ->willReturn($response);

        $this->sut->setResponseFactory($this->factory->reveal());

        self::assertSame($response, $this->sut->handle($request));
    }
}
