<?php

namespace Phpactor\LanguageServer\Tests\Unit\Core\Handler;

use Phpactor\TestUtils\PHPUnit\TestCase;
use Phpactor\LanguageServer\Core\Handler\Handler;
use Phpactor\LanguageServer\Core\Handler\HandlerNotFound;
use Phpactor\LanguageServer\Core\Handler\Handlers;
use Phpactor\LanguageServer\Core\Handler\ServiceProvider;
use RuntimeException;

class HandlersTest extends TestCase
{
    /**
     * @var ObjectProphecy
     */
    private $handler1;

    /**
     * @var ObjectProphecy
     */
    private $handler2;

    protected function setUp(): void
    {
        $this->handler1 = $this->prophesize(Handler::class);
        $this->handler2 = $this->prophesize(Handler::class);
        $this->service1 = $this->prophesize(ServiceProvider::class);
    }

    public function testThrowsExceptionNotFound()
    {
        $this->expectException(HandlerNotFound::class);
        $this->handler1->methods()->willReturn(['barbra']);
        $handlers = $this->create([ $this->handler1->reveal() ]);
        $handlers->get('foobar');
    }

    public function testReturnsHandler()
    {
        $this->handler1->methods()->willReturn(['foobar' => 'foobar']);
        $handlers = $this->create([ $this->handler1->reveal() ]);
        $handler = $handlers->get('foobar');
        $this->assertSame($this->handler1->reveal(), $handler);
    }

    public function testReturnsServicesWhenRegisterdAsList()
    {
        $this->service1->services()->willReturn(['foobar']);
        $this->service1->methods()->willReturn([]);
        $handlers = $this->create([ $this->service1->reveal() ]);
        $services = $handlers->services();
        self::assertCount(1, $services);
        self::assertEquals('foobar', key($services));
    }

    public function testThrowsExceptionIfServiceKeyIsString()
    {
        $this->expectException(RuntimeException::class);
        $this->service1->services()->willReturn(['foobar' => 'foobar']);
        $this->service1->methods()->willReturn([]);
        $this->create([ $this->service1->reveal() ]);
    }

    public function testMerge()
    {
        $this->handler1->methods()->willReturn(['foobar' => 'foobar']);
        $this->handler2->methods()->willReturn(['barfoo' => 'barfoo']);

        $handlers = $this->create([ $this->handler1->reveal() ]);
        $handlers->merge($this->create([ $this->handler2->reveal() ]));

        $this->assertCount(2, $handlers);
        $this->assertEquals(['foobar', 'barfoo'], array_keys($handlers->methods()));
    }

    private function create(array $handlers): Handlers
    {
        return new Handlers($handlers);
    }
}
