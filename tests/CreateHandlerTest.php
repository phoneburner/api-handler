<?php

declare(strict_types=1);

namespace PhoneBurner\Tests\ApiHandler;

use PhoneBurner\ApiHandler\CreateHandler;
use PhoneBurner\ApiHandler\Hydrator;
use PhoneBurner\ApiHandler\ResponseFactory;
use PhoneBurner\ApiHandler\TransformableResource;
use PhoneBurner\ApiHandler\Transformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

final class CreateHandlerTest extends TestCase
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
     * @var ObjectProphecy<Transformer>
     */
    private ObjectProphecy $transformer;

    private CreateHandler $sut;

    protected function setUp(): void
    {
        $this->hydrator = $this->prophesize(Hydrator::class);
        $this->transformer = $this->prophesize(Transformer::class);

        $this->sut = new CreateHandler(
            $this->hydrator->reveal(),
            $this->transformer->reveal(),
        );

        $this->factory = $this->prophesize(ResponseFactory::class);
    }

    #[Test]
    public function handleResolvesResourceAndReturnsUpdatedResource(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $created = new stdClass();
        $this->hydrator->create($request)->willReturn($created)->shouldBeCalledOnce();

        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $transformable_resource = new TransformableResource($created, $request, $this->transformer->reveal());
        $this->factory->make($transformable_resource, 201)
            ->willReturn($response);

        $this->sut->setResponseFactory($this->factory->reveal());

        self::assertSame($response, $this->sut->handle($request));
    }

    #[Test]
    public function handleAllowsNullResourceAndReturnsAccepted(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $this->hydrator->create($request)->willReturn(null)->shouldBeCalledOnce();

        $this->sut->setResponseFactory($this->factory->reveal());
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->factory->make(null, 204)
            ->willReturn($response);

        self::assertSame($response, $this->sut->handle($request));
    }
}
