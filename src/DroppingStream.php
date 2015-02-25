<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamableInterface;

/**
 * Stream decorator that begins dropping data once the size of the underlying
 * stream becomes too full.
 */
class DroppingStream implements StreamableInterface
{
    use StreamDecoratorTrait;

    private $maxLength;

    /**
     * @param StreamableInterface $stream    Underlying stream to decorate.
     * @param int             $maxLength Maximum size before dropping data.
     */
    public function __construct(StreamableInterface $stream, $maxLength)
    {
        $this->stream = $stream;
        $this->maxLength = $maxLength;
    }

    public function write($string)
    {
        $diff = $this->maxLength - $this->stream->getSize();

        // Begin returning false when the underlying stream is too large.
        if ($diff <= 0) {
            return false;
        }

        // Write the stream or a subset of the stream if needed.
        if (strlen($string) < $diff) {
            return $this->stream->write($string);
        }

        $this->stream->write(substr($string, 0, $diff));

        return false;
    }
}
