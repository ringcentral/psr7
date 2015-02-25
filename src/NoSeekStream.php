<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamableInterface;

/**
 * Stream decorator that prevents a stream from being seeked
 */
class NoSeekStream implements StreamableInterface
{
    use StreamDecoratorTrait;

    public function seek($offset, $whence = SEEK_SET)
    {
        return false;
    }

    public function isSeekable()
    {
        return false;
    }
}
