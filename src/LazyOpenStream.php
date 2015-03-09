<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamableInterface;

/**
 * Lazily reads or writes to a file that is opened only after an IO operation
 * take place on the stream.
 */
class LazyOpenStream implements StreamableInterface
{
    use StreamDecoratorTrait;

    /** @var string File to open */
    private $filename;

    /** @var string $mode */
    private $mode;

    /**
     * @param string $filename File to lazily open
     * @param string $mode     fopen mode to use when opening the stream
     */
    public function __construct($filename, $mode)
    {
        $this->filename = $filename;
        $this->mode = $mode;
    }

    /**
     * Creates the underlying stream lazily when required.
     *
     * @return StreamableInterface
     */
    protected function createStream()
    {
        return Stream::factory(try_fopen($this->filename, $this->mode));
    }
}
