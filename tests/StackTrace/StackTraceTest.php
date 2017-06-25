<?php

namespace Facile\Sentry\CommonTest\StackTrace;

use Facile\Sentry\Common\StackTrace\StackTrace;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class StackTraceTest extends TestCase
{
    public function testGetExceptions()
    {
        $client = $this->prophesize(\Raven_Client::class);
        $serializer = $this->prophesize(\Raven_Serializer::class);
        $reprSerializer = $this->prophesize(\Raven_ReprSerializer::class);
        $ignoreBacktraceNamespaces = [];

        $serializer->serialize(Argument::any())->shouldBeCalled()->willReturnArgument(0);
        $reprSerializer->serialize(Argument::any())->willReturnArgument(0);

        $stackTrace = new StackTrace(
            $client->reveal(),
            $serializer->reveal(),
            $reprSerializer->reveal(),
            $ignoreBacktraceNamespaces
        );

        $previousException = new \OutOfBoundsException('previous', 0);
        $exception = new \RuntimeException('message', 0, $previousException);

        $result = $stackTrace->getExceptions($exception);

        $this->assertCount(2, $result);
        $this->assertEquals('previous', $result[0]['value']);
        $this->assertEquals('OutOfBoundsException', $result[0]['type']);
        $this->assertArrayHasKey('stacktrace', $result[0]);
        $this->assertArrayHasKey('frames', $result[0]['stacktrace']);
        $this->assertNotEmpty($result[0]['stacktrace']['frames']);

        $this->assertEquals('message', $result[1]['value']);
        $this->assertEquals('RuntimeException', $result[1]['type']);
        $this->assertArrayHasKey('stacktrace', $result[1]);
        $this->assertArrayHasKey('frames', $result[1]['stacktrace']);
        $this->assertNotEmpty($result[1]['stacktrace']['frames']);
    }

    public function testGetExceptionsWithDefaultsDeps()
    {
        $client = $this->prophesize(\Raven_Client::class);

        $stackTrace = new StackTrace(
            $client->reveal()
        );

        $previousException = new \OutOfBoundsException('previous', 0);
        $exception = new \RuntimeException('message', 0, $previousException);

        $result = $stackTrace->getExceptions($exception);

        $this->assertCount(2, $result);
        $this->assertEquals('previous', $result[0]['value']);
        $this->assertEquals('OutOfBoundsException', $result[0]['type']);
        $this->assertArrayHasKey('stacktrace', $result[0]);
        $this->assertArrayHasKey('frames', $result[0]['stacktrace']);
        $this->assertNotEmpty($result[0]['stacktrace']['frames']);

        $this->assertEquals('message', $result[1]['value']);
        $this->assertEquals('RuntimeException', $result[1]['type']);
        $this->assertArrayHasKey('stacktrace', $result[1]);
        $this->assertArrayHasKey('frames', $result[1]['stacktrace']);
        $this->assertNotEmpty($result[1]['stacktrace']['frames']);
    }

    public function testCleanBacktrace()
    {
        $trace = [
            ['class' => 'Test\\Foo'],
            ['class' => 'Test\\Bar'],
            ['class' => 'App\\Foo'],
            ['file' => 'foo'],
            ['file' => 'bar'],
        ];

        $ignoreNamespaces = [
            'Test\\',
        ];

        $client = $this->prophesize(\Raven_Client::class);

        $stackTrace = new StackTrace($client->reveal(), null, null, $ignoreNamespaces);

        $result = $stackTrace->cleanBacktrace($trace);

        $expected = [
            ['class' => 'App\\Foo'],
            ['file' => 'foo'],
            ['file' => 'bar'],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testCleanBacktraceWithNoClassStack()
    {
        $trace = [
            ['class' => 'Test\\Foo'],
            ['class' => 'Test\\Bar'],
            ['file' => 'foo'],
            ['file' => 'bar'],
        ];

        $ignoreNamespaces = [
            'Test\\',
        ];

        $client = $this->prophesize(\Raven_Client::class);

        $stackTrace = new StackTrace($client->reveal(), null, null, $ignoreNamespaces);

        $result = $stackTrace->cleanBacktrace($trace);

        $expected = [
            ['file' => 'foo'],
            ['file' => 'bar'],
        ];

        $this->assertEquals($expected, $result);
    }
}
