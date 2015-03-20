<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Stream;

class FunctionsTest extends \PHPUnit_Framework_TestCase
{
    public function testCopiesToString()
    {
        $s = Stream::factory('foobaz');
        $this->assertEquals('foobaz', Psr7\copy_to_string($s));
        $s->seek(0);
        $this->assertEquals('foo', Psr7\copy_to_string($s, 3));
        $this->assertEquals('baz', Psr7\copy_to_string($s, 3));
        $this->assertEquals('', Psr7\copy_to_string($s));
    }

    public function testCopiesToStringStopsWhenReadFails()
    {
        $s1 = Stream::factory('foobaz');
        $s1 = FnStream::decorate($s1, [
            'read' => function () {
                return false;
            }
        ]);
        $result = Psr7\copy_to_string($s1);
        $this->assertEquals('', $result);
    }

    public function testCopiesToStream()
    {
        $s1 = Stream::factory('foobaz');
        $s2 = Stream::factory('');
        Psr7\copy_to_stream($s1, $s2);
        $this->assertEquals('foobaz', (string) $s2);
        $s2 = Stream::factory('');
        $s1->seek(0);
        Psr7\copy_to_stream($s1, $s2, 3);
        $this->assertEquals('foo', (string) $s2);
        Psr7\copy_to_stream($s1, $s2, 3);
        $this->assertEquals('foobaz', (string) $s2);
    }

    public function testStopsCopyToStreamWhenWriteFails()
    {
        $s1 = Stream::factory('foobaz');
        $s2 = Stream::factory('');
        $s2 = FnStream::decorate($s2, ['write' => function () { return 0; }]);
        Psr7\copy_to_stream($s1, $s2);
        $this->assertEquals('', (string) $s2);
    }

    public function testStopsCopyToSteamWhenWriteFailsWithMaxLen()
    {
        $s1 = Stream::factory('foobaz');
        $s2 = Stream::factory('');
        $s2 = FnStream::decorate($s2, ['write' => function () { return 0; }]);
        Psr7\copy_to_stream($s1, $s2, 10);
        $this->assertEquals('', (string) $s2);
    }

    public function testStopsCopyToSteamWhenReadFailsWithMaxLen()
    {
        $s1 = Stream::factory('foobaz');
        $s1 = FnStream::decorate($s1, ['read' => function () { return ''; }]);
        $s2 = Stream::factory('');
        Psr7\copy_to_stream($s1, $s2, 10);
        $this->assertEquals('', (string) $s2);
    }

    public function testReadsLines()
    {
        $s = Stream::factory("foo\nbaz\nbar");
        $this->assertEquals("foo\n", Psr7\readline($s));
        $this->assertEquals("baz\n", Psr7\readline($s));
        $this->assertEquals("bar", Psr7\readline($s));
    }

    public function testReadsLinesUpToMaxLength()
    {
        $s = Stream::factory("12345\n");
        $this->assertEquals("123", Psr7\readline($s, 4));
        $this->assertEquals("45\n", Psr7\readline($s));
    }

    public function testReadsLineUntilFalseReturnedFromRead()
    {
        $s = $this->getMockBuilder('GuzzleHttp\Psr7\Stream')
            ->setMethods(['read', 'eof'])
            ->disableOriginalConstructor()
            ->getMock();
        $s->expects($this->exactly(2))
            ->method('read')
            ->will($this->returnCallback(function () {
                static $c = false;
                if ($c) {
                    return false;
                }
                $c = true;
                return 'h';
            }));
        $s->expects($this->exactly(2))
            ->method('eof')
            ->will($this->returnValue(false));
        $this->assertEquals("h", Psr7\readline($s));
    }

    public function testCalculatesHash()
    {
        $s = Stream::factory('foobazbar');
        $this->assertEquals(md5('foobazbar'), Psr7\hash($s, 'md5'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testCalculatesHashThrowsWhenSeekFails()
    {
        $s = new NoSeekStream(Stream::factory('foobazbar'));
        $s->read(2);
        Psr7\hash($s, 'md5');
    }

    public function testCalculatesHashSeeksToOriginalPosition()
    {
        $s = Stream::factory('foobazbar');
        $s->seek(4);
        $this->assertEquals(md5('foobazbar'), Psr7\hash($s, 'md5'));
        $this->assertEquals(4, $s->tell());
    }

    public function testOpensFilesSuccessfully()
    {
        $r = Psr7\try_fopen(__FILE__, 'r');
        $this->assertInternalType('resource', $r);
        fclose($r);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to open /path/to/does/not/exist using mode r
     */
    public function testThrowsExceptionNotWarning()
    {
        Psr7\try_fopen('/path/to/does/not/exist', 'r');
    }

    public function parseQueryProvider()
    {
        return [
            // Does not need to parse when the string is empty
            ['', []],
            // Can parse mult-values items
            ['q=a&q=b', ['q' => ['a', 'b']]],
            // Can parse multi-valued items that use numeric indices
            ['q[0]=a&q[1]=b', ['q[0]' => 'a', 'q[1]' => 'b']],
            // Can parse duplicates and does not include numeric indices
            ['q[]=a&q[]=b', ['q[]' => ['a', 'b']]],
            // Ensures that the value of "q" is an array even though one value
            ['q[]=a', ['q[]' => 'a']],
            // Does not modify "." to "_" like PHP's parse_str()
            ['q.a=a&q.b=b', ['q.a' => 'a', 'q.b' => 'b']],
            // Can decode %20 to " "
            ['q%20a=a%20b', ['q a' => 'a b']],
            // Can parse funky strings with no values by assigning each to null
            ['q&a', ['q' => null, 'a' => null]],
            // Does not strip trailing equal signs
            ['data=abc=', ['data' => 'abc=']],
            // Can store duplicates without affecting other values
            ['foo=a&foo=b&?µ=c', ['foo' => ['a', 'b'], '?µ' => 'c']],
            // Sets value to null when no "=" is present
            ['foo', ['foo' => null]],
            // Preserves "0" keys.
            ['0', ['0' => null]],
            // Sets the value to an empty string when "=" is present
            ['0=', ['0' => '']],
            // Preserves falsey keys
            ['var=0', ['var' => '0']],
            ['a[b][c]=1&a[b][c]=2', ['a[b][c]' => ['1', '2']]],
            ['a[b]=c&a[d]=e', ['a[b]' => 'c', 'a[d]' => 'e']],
            // Ensure it doesn't leave things behind with repeated values
            // Can parse mult-values items
            ['q=a&q=b&q=c', ['q' => ['a', 'b', 'c']]],
        ];
    }

    /**
     * @dataProvider parseQueryProvider
     */
    public function testParsesQueries($input, $output)
    {
        $result = Psr7\parse_query($input);
        $this->assertSame($output, $result);
    }

    public function testDoesNotDecode()
    {
        $str = 'foo%20=bar';
        $data = Psr7\parse_query($str, false);
        $this->assertEquals(['foo%20' => 'bar'], $data);
    }

    /**
     * @dataProvider parseQueryProvider
     */
    public function testParsesAndBuildsQueries($input, $output)
    {
        $result = Psr7\parse_query($input, false);
        $this->assertSame($input, Psr7\build_query($result, false));
    }

    public function testEncodesWithRfc1738()
    {
        $str = Psr7\build_query(['foo bar' => 'baz+'], PHP_QUERY_RFC1738);
        $this->assertEquals('foo+bar=baz%2B', $str);
    }

    public function testEncodesWithRfc3986()
    {
        $str = Psr7\build_query(['foo bar' => 'baz+'], PHP_QUERY_RFC3986);
        $this->assertEquals('foo%20bar=baz%2B', $str);
    }

    public function testDoesNotEncode()
    {
        $str = Psr7\build_query(['foo bar' => 'baz+'], false);
        $this->assertEquals('foo bar=baz+', $str);
    }

    public function testCanControlDecodingType()
    {
        $result = Psr7\parse_query('var=foo+bar', PHP_QUERY_RFC3986);
        $this->assertEquals('foo+bar', $result['var']);
        $result = Psr7\parse_query('var=foo+bar', PHP_QUERY_RFC1738);
        $this->assertEquals('foo bar', $result['var']);
    }

    public function testParsesRequestMessages()
    {
        $req = "GET /abc HTTP/1.0\r\nHost: foo.com\r\nFoo: Bar\r\nBaz: Bam\r\nBaz: Qux\r\n\r\nTest";
        $request = Psr7\parse_request($req);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/abc', $request->getRequestTarget());
        $this->assertEquals('1.0', $request->getProtocolVersion());
        $this->assertEquals('foo.com', $request->getHeader('Host'));
        $this->assertEquals('Bar', $request->getHeader('Foo'));
        $this->assertEquals('Bam, Qux', $request->getHeader('Baz'));
        $this->assertEquals('Test', (string) $request->getBody());
        $this->assertEquals('http://foo.com/abc', (string) $request->getUri());
    }

    public function testParsesRequestMessagesWithHttpsScheme()
    {
        $req = "PUT /abc?baz=bar HTTP/1.1\r\nHost: foo.com:443\r\n\r\n";
        $request = Psr7\parse_request($req);
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals('/abc?baz=bar', $request->getRequestTarget());
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals('foo.com:443', $request->getHeader('Host'));
        $this->assertEquals('', (string) $request->getBody());
        $this->assertEquals('https://foo.com/abc?baz=bar', (string) $request->getUri());
    }

    public function testParsesRequestMessagesWithUriWhenHostIsNotFirst()
    {
        $req = "PUT / HTTP/1.1\r\nFoo: Bar\r\nHost: foo.com\r\n\r\n";
        $request = Psr7\parse_request($req);
        $this->assertEquals('PUT', $request->getMethod());
        $this->assertEquals('/', $request->getRequestTarget());
        $this->assertEquals('http://foo.com/', (string) $request->getUri());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatesRequestMessages()
    {
        Psr7\parse_request("HTTP/1.1 200 OK\r\n\r\n");
    }

    public function testParsesResponseMessages()
    {
        $res = "HTTP/1.0 200 OK\r\nFoo: Bar\r\nBaz: Bam\r\nBaz: Qux\r\n\r\nTest";
        $response = Psr7\parse_response($res);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
        $this->assertEquals('1.0', $response->getProtocolVersion());
        $this->assertEquals('Bar', $response->getHeader('Foo'));
        $this->assertEquals('Bam, Qux', $response->getHeader('Baz'));
        $this->assertEquals('Test', (string) $response->getBody());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testValidatesResponseMessages()
    {
        Psr7\parse_response("GET / HTTP/1.1\r\n\r\n");
    }

    public function testDetermineMimetype()
    {
        $this->assertNull(Psr7\mimetype_from_extension('not-a-real-extension'));
        $this->assertEquals(
            'application/json',
            Psr7\mimetype_from_extension('json')
        );
        $this->assertEquals(
            'image/jpeg',
            Psr7\mimetype_from_filename('/tmp/images/IMG034821.JPEG')
        );
    }
}
