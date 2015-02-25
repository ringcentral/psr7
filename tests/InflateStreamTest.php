<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7\InflateStream;
use GuzzleHttp\Psr7\Stream;

class InflateStreamtest extends \PHPUnit_Framework_TestCase
{
    public function testInflatesStreams()
    {
        $content = gzencode('test');
        $a = Stream::factory($content);
        $b = new InflateStream($a);
        $this->assertEquals('test', (string) $b);
    }
}
