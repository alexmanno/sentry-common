<?php
declare(strict_types=1);

namespace Facile\Sentry\CommonTest\Sanitizer;

use Facile\Sentry\Common\Sanitizer\Sanitizer;
use PHPUnit\Framework\TestCase;

class TestObject
{

}

class SanitizerTest extends TestCase
{
    public function testSanitizeWithTraversable()
    {
        $data = new \ArrayObject([
            'foo' => 'bar',
            'items' => [
                'string' => 'string',
                'int' => 2,
                'float' => 2.2,
                'bool' => true,
                'toStringObject' => new class {
                    public function __toString()
                    {
                        return 'ObjectClass';
                    }
                },
                'object' => new TestObject(),
                'resource' => fopen('php://temp', 'rb+'),
            ]
        ]);
        $expected = [
            'foo' => 'bar',
            'items' => [
                'string' => 'string',
                'int' => 2,
                'float' => 2.2,
                'bool' => true,
                'toStringObject' => 'ObjectClass',
                'object' => 'Facile\Sentry\CommonTest\Sanitizer\TestObject',
                'resource' => 'stream',
            ]
        ];

        $sanitizer = new Sanitizer();
        $result = $sanitizer->sanitize($data);

        $this->assertSame($expected, $result);
    }
}
