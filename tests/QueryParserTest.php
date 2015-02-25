<?php
namespace GuzzleHttp\Psr7\Tests;

use GuzzleHttp\Psr7\QueryParser;

class QueryParserTest extends \PHPUnit_Framework_TestCase
{
    public function parseQueryProvider()
    {
        return [
            // Does not need to parse when the string is empty
            ['', []],
            // Can parse mult-values items
            ['q=a&q=b', ['q' => ['a', 'b']]],
            // Can parse multi-valued items that use numeric indices
            ['q[0]=a&q[1]=b', ['q' => ['a', 'b']]],
            // Can parse duplicates and does not include numeric indices
            ['q[]=a&q[]=b', ['q' => ['a', 'b']]],
            // Ensures that the value of "q" is an array even though one value
            ['q[]=a', ['q' => ['a']]],
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
            // Can deeply nest and store duplicate PHP values
            ['a[b][c]=1&a[b][c]=2', [
                'a' => ['b' => ['c' => ['1', '2']]]
            ]],
            // Can parse PHP style arrays
            ['a[b]=c&a[d]=e', ['a' => ['b' => 'c', 'd' => 'e']]],
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
        $parser = new QueryParser();
        $result = $parser->parse($input);
        $this->assertEquals($output, $result['data']);
    }

    public function testCanControlDecodingType()
    {
        $qp = new QueryParser();
        $result = $qp->parse('var=foo+bar', PHP_QUERY_RFC3986);
        $this->assertEquals('foo+bar', $result['data']['var']);
        $result = $qp->parse('var=foo+bar', PHP_QUERY_RFC1738);
        $this->assertEquals('foo bar', $result['data']['var']);
    }
}
