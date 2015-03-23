<?php
namespace GuzzleHttp\Tests\Psr7;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\NoSeekStream;

/**
 * @covers GuzzleHttp\Psr7\NoSeekStream
 * @covers GuzzleHttp\Psr7\StreamDecoratorTrait
 */
class NoSeekStreamTest extends \PHPUnit_Framework_TestCase
{
    public function testCannotSeek()
    {
        $s = $this->getMockBuilder('Psr\Http\Message\StreamableInterface')
            ->setMethods(['isSeekable', 'seek'])
            ->getMockForAbstractClass();
        $s->expects($this->never())->method('seek');
        $s->expects($this->never())->method('isSeekable');
        $wrapped = new NoSeekStream($s);
        $this->assertFalse($wrapped->isSeekable());
        $this->assertFalse($wrapped->seek(2));
    }

    public function testHandlesClose()
    {
        $s = Psr7\stream_for('foo');
        $wrapped = new NoSeekStream($s);
        $wrapped->close();
        $this->assertFalse($wrapped->write('foo'));
    }
}
