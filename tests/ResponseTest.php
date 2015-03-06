<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\Response;

/**
 * @covers GuzzleHttp\Psr7\MessageTrait
 * @covers GuzzleHttp\Psr7\Response
 */
class ResponseTest extends \PHPUnit_Framework_TestCase
{
    public function testAddsDefaultReason()
    {
        $r = new Response('200');
        $this->assertSame(200, $r->getStatusCode());
        $this->assertEquals('OK', $r->getReasonPhrase());
    }

    public function testCanGiveCustomReason()
    {
        $r = new Response(200, [], null, '1.1', 'bar');
        $this->assertEquals('bar', $r->getReasonPhrase());
    }

    public function testCanGiveCustomProtocolVersion()
    {
        $r = new Response(200, [], null, '1000');
        $this->assertEquals('1000', $r->getProtocolVersion());
    }

    public function testCanCreateNewResponseWithStatusAndNoReason()
    {
        $r = new Response(200);
        $r2 = $r->withStatus(201);
        $this->assertEquals(200, $r->getStatusCode());
        $this->assertEquals('OK', $r->getReasonPhrase());
        $this->assertEquals(201, $r2->getStatusCode());
        $this->assertEquals('Created', $r2->getReasonPhrase());
    }

    public function testCanCreateNewResponseWithStatusAndReason()
    {
        $r = new Response(200);
        $r2 = $r->withStatus(201, 'Foo');
        $this->assertEquals(200, $r->getStatusCode());
        $this->assertEquals('OK', $r->getReasonPhrase());
        $this->assertEquals(201, $r2->getStatusCode());
        $this->assertEquals('Foo', $r2->getReasonPhrase());
    }

    public function testCreatesResponseWithAddedHeaderArray()
    {
        $r = new Response();
        $r2 = $r->withAddedHeader('foo', ['baz', 'bar']);
        $this->assertFalse($r->hasHeader('foo'));
        $this->assertEquals('baz, bar', $r2->getHeader('foo'));
    }

    public function testReturnsIdentityWhenRemovingMissingHeader()
    {
        $r = new Response();
        $this->assertSame($r, $r->withoutHeader('foo'));
    }

    public function testAlwaysReturnsBody()
    {
        $r = new Response();
        $this->assertInstanceOf('Psr\Http\Message\StreamableInterface', $r->getBody());
    }

    public function testCanSetHeaderAsArray()
    {
        $r = new Response(200, [
            'foo' => ['baz ', ' bar ']
        ]);
        $this->assertEquals('baz, bar', $r->getHeader('foo'));
        $this->assertEquals(['baz', 'bar'], $r->getHeaderLines('foo'));
    }
}
