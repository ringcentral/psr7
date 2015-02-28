<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\Uri;

/**
 * @covers GuzzleHttp\Psr7\Uri
 */
class UriTest extends \PHPUnit_Framework_TestCase
{
    const RFC3986_BASE = "http://a/b/c/d;p?q";

    public function testParsesProvidedUrl()
    {
        $uri = new Uri('https://michael:test@test.com:443/path/123?q=abc#test');

        // Standard port 443 for https gets ignored.
        $this->assertEquals(
            'https://michael:test@test.com/path/123?q=abc#test',
            (string) $uri
        );

        $this->assertEquals('test', $uri->getFragment());
        $this->assertEquals('test.com', $uri->getHost());
        $this->assertEquals('/path/123', $uri->getPath());
        $this->assertEquals(443, $uri->getPort());
        $this->assertEquals('q=abc', $uri->getQuery());
        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('michael:test', $uri->getUserInfo());
    }

    public function testCanTransformAndRetrievePartsIndividually()
    {
        $uri = (new Uri(''))
            ->withFragment('#test')
            ->withHost('example.com')
            ->withPath('path/123')
            ->withPort(8080)
            ->withQuery('?q=abc')
            ->withScheme('http')
            ->withUserInfo('user', 'pass');

        // Test getters.
        $this->assertEquals('user:pass@example.com:8080', $uri->getAuthority());
        $this->assertEquals('test', $uri->getFragment());
        $this->assertEquals('example.com', $uri->getHost());
        $this->assertEquals('/path/123', $uri->getPath());
        $this->assertEquals(8080, $uri->getPort());
        $this->assertEquals('q=abc', $uri->getQuery());
        $this->assertEquals('http', $uri->getScheme());
        $this->assertEquals('user:pass', $uri->getUserInfo());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSchemeMustBeValid()
    {
        (new Uri(''))->withScheme('foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPortMustBeValid()
    {
        (new Uri(''))->withPort(100000);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPathMustBeValid()
    {
        (new Uri(''))->withPath([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testQueryMustBeValid()
    {
        (new Uri(''))->withQuery(new \stdClass);
    }


    public function testAllowsFalseyUrlParts()
    {
        $url = new Uri('http://a:1/0?0#0');
        $this->assertSame('a', $url->getHost());
        $this->assertEquals(1, $url->getPort());
        $this->assertSame('/0', $url->getPath());
        $this->assertEquals('0', (string) $url->getQuery());
        $this->assertSame('0', $url->getFragment());
        $this->assertEquals('http://a:1/0?0#0', (string) $url);

        $url = new Uri('');
        $this->assertSame('', (string) $url);

        $url = new Uri('0');
        $this->assertSame('/0', (string) $url);
    }
}