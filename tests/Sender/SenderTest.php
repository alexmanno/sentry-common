<?php

namespace Facile\Sentry\CommonTest\Sender;

use Facile\Sentry\Common\Sanitizer\SanitizerInterface;
use Facile\Sentry\Common\Sender\Sender;
use Facile\Sentry\Common\StackTrace\StackTraceInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Raven_Client;

class SenderTest extends TestCase
{
    public function testGetters()
    {
        $client = $this->prophesize(Raven_Client::class);
        $sanitizer = $this->prophesize(SanitizerInterface::class);
        $stackTrace = $this->prophesize(StackTraceInterface::class);

        $sender = new Sender(
            $client->reveal(),
            $sanitizer->reveal(),
            $stackTrace->reveal()
        );

        $this->assertSame($client->reveal(), $sender->getClient());
        $this->assertSame($sanitizer->reveal(), $sender->getSanitizer());
        $this->assertSame($stackTrace->reveal(), $sender->getStackTrace());
    }

    public function testSend()
    {
        $client = $this->prophesize(Raven_Client::class);
        $sanitizer = $this->prophesize(SanitizerInterface::class);
        $stackTrace = $this->prophesize(StackTraceInterface::class);

        $object = new \stdClass();

        $sanitizer->sanitize([
            'foo' => 'bar',
            'object' => $object,
        ])
            ->shouldBeCalled()
            ->willReturn([
                'foo' => 'bar',
                'object' => 'stdClass',
            ]);

        $stackTrace->addIgnoreBacktraceNamespace('Facile\\Sentry\\Common\\Sender')->shouldBeCalled();

        $sender = new Sender(
            $client->reveal(),
            $sanitizer->reveal(),
            $stackTrace->reveal()
        );

        $client->captureMessage(
            Argument::exact('message'),
            Argument::exact([]),
            Argument::allOf(
                Argument::withEntry(
                    'extra',
                    [
                        'foo' => 'bar',
                        'object' => 'stdClass',
                    ]
                ),
                Argument::withEntry('level', 'warning')
            )
        )->shouldBeCalled();

        $sender->send(
            'warning',
            'message',
            [
                'foo' => 'bar',
                'object' => new \stdClass(),
            ]
        );
    }

    public function testSendWithExceptionInContext()
    {
        $client = $this->prophesize(Raven_Client::class);
        $sanitizer = $this->prophesize(SanitizerInterface::class);
        $stackTrace = $this->prophesize(StackTraceInterface::class);

        $object = new \stdClass();

        $sanitizer->sanitize([
            'foo' => 'bar',
            'object' => $object,
        ])
            ->shouldBeCalled()
            ->willReturn([
                'foo' => 'bar',
                'object' => 'stdClass',
            ]);

        $stackTrace->addIgnoreBacktraceNamespace('Facile\\Sentry\\Common\\Sender')->shouldBeCalled();

        $sender = new Sender(
            $client->reveal(),
            $sanitizer->reveal(),
            $stackTrace->reveal()
        );

        $stackTrace->getExceptions(Argument::type(\RuntimeException::class))
            ->shouldBeCalled()
            ->willReturn([]);

        $stackTrace->cleanBacktrace(Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn([]);

        $stackTrace->getStackTraceFrames(Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn([]);

        $client->captureMessage(
            Argument::exact('message'),
            Argument::exact([]),
            Argument::allOf(
                Argument::withEntry(
                    'extra',
                    [
                        'foo' => 'bar',
                        'object' => $object,
                    ]
                ),
                Argument::withEntry('level', 'warning'),
                Argument::withEntry('exception', Argument::allOf(
                    Argument::withEntry('values', [
                        [
                            'value' => 'test exception',
                            'type' => 'RuntimeException',
                            'stacktrace' => [
                                'frames' => []
                            ]
                        ]
                    ])
                ))
            )
        );

        $sender->send(
            'warning',
            'message',
            [
                'foo' => 'bar',
                'object' => new \stdClass(),
                'exception' => new \RuntimeException('test exception'),
            ]
        );
    }
}
