<?php

namespace Phpactor\LanguageServer\Tests\Unit\Core\Diagnostics;

use Amp\CancellationTokenSource;
use Amp\Delayed;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Success;
use Generator;
use Phpactor\LanguageServerProtocol\TextDocumentItem;
use Phpactor\LanguageServer\Core\Diagnostics\ClosureDiagnosticsProvider;
use Phpactor\LanguageServer\Core\Diagnostics\DiagnosticsEngine;
use Phpactor\LanguageServer\LanguageServerTesterBuilder;
use Phpactor\LanguageServer\Test\ProtocolFactory;

class DiagnosticsEngineTest extends AsyncTestCase
{
    /**
     * @return Generator<mixed>
     */
    public function testPublishesDiagnostics(): Generator
    {
        $tester = LanguageServerTesterBuilder::create();
        $engine = $this->createEngine($tester);

        $token = new CancellationTokenSource();
        $promise = $engine->run($token->getToken());

        $engine->enqueue(
            ProtocolFactory::textDocumentItem('file:///foobar', 'foobar')
        );

        yield new Delayed(10);

        $token->cancel();

        $notification = $tester->transmitter()->shiftNotification();

        self::assertNotNull($notification);
        self::assertEquals('diagnostics/publishDiagnostics', $notification->method);
    }

    /**
     * @return Generator<mixed>
     */
    public function testPublishesForManyFiles(): Generator
    {
        $tester = LanguageServerTesterBuilder::create();
        $engine = $this->createEngine($tester);

        $token = new CancellationTokenSource();
        $promise = $engine->run($token->getToken());

        $engine->enqueue(ProtocolFactory::textDocumentItem('file:///foobar', 'foobar'));
        $engine->enqueue(ProtocolFactory::textDocumentItem('file:///barfoo', 'foobar'));
        $engine->enqueue(ProtocolFactory::textDocumentItem('file:///bazbar', 'foobar'));

        yield new Delayed(10);

        $token->cancel();

        self::assertEquals(3, $tester->transmitter()->count());
    }

    private function createEngine(LanguageServerTesterBuilder $tester): DiagnosticsEngine
    {
        $engine = new DiagnosticsEngine($tester->clientApi(), new ClosureDiagnosticsProvider(function (TextDocumentItem $item) {
            return new Success([
                ProtocolFactory::diagnostic(
                    ProtocolFactory::range(0, 0, 0, 0),
                    'Foobar is broken'
                )
            ]);
        }));
        return $engine;
    }
}
