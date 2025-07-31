<?php

declare(strict_types=1);

namespace PhoneBurner\Tests\ApiHandler;

use PhoneBurner\ApiHandler\DispatchMiddleware as SUT;
use PhoneBurner\ApiHandler\HandlerFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DispatchMiddlewareTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy<HandlerFactory>
     */
    private ObjectProphecy $factory;

    private SUT $sut;

    protected function setUp(): void
    {
        $this->factory = $this->prophesize(HandlerFactory::class);
        $this->sut = new SUT($this->factory->reveal());
    }

    #[Test]
    public function processPassesIfFactoryCannotHandle(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $this->factory->canHandle($request->reveal())->willReturn(false);

        $next = $this->prophesize(RequestHandlerInterface::class);
        $response = $this->prophesize(ResponseInterface::class);

        $next->handle($request->reveal())->willReturn($response->reveal());

        self::assertSame($response->reveal(), $this->sut->process($request->reveal(), $next->reveal()));
    }

    #[Test]
    public function processCreatesHandlerAndCalls(): void
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $this->factory->canHandle($request->reveal())->willReturn(true);

        $handler = $this->prophesize(RequestHandlerInterface::class);
        $response = $this->prophesize(ResponseInterface::class);
        $handler->handle($request->reveal())->willReturn($response->reveal());

        $this->factory->makeForRequest($request->reveal())->willReturn($handler->reveal());

        $next = $this->prophesize(RequestHandlerInterface::class);

        self::assertSame($response->reveal(), $this->sut->process($request->reveal(), $next->reveal()));
    }
}
