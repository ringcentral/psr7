<?php
namespace GuzzleHttp\Psr7;

use Psr\Http\Message\StreamableInterface;

/**
 * Trait implementing functionality common to requests and responses.
 */
trait MessageTrait
{
    /** @var array HTTP header collection */
    private $headers = [];

    /** @var array mapping a lowercase header name to its name over the wire */
    private $headerNames = [];

    /** @var string */
    private $protocol = '1.1';

    /** @var StreamableInterface */
    private $stream;

    public function getProtocolVersion()
    {
        return $this->protocol;
    }

    public function withProtocolVersion($version)
    {
        $new = clone $this;
        $new->protocol = $version;
        return $new;
    }

    public function getHeaders()
    {
        $headers = [];
        foreach ($this->headers as $name => $values) {
            $headers[$this->headerNames[$name]] = $values;
        }

        return $headers;
    }

    public function hasHeader($header)
    {
        return isset($this->headers[strtolower($header)]);
    }

    public function getHeader($header)
    {
        $name = strtolower($header);

        return isset($this->headers[$name])
            ? implode(', ', $this->headers[$name])
            : '';
    }

    public function getHeaderLines($header)
    {
        $name = strtolower($header);
        return isset($this->headers[$name]) ? $this->headers[$name] : [];
    }

    public function withHeader($header, $value)
    {
        $new = clone $this;
        $header = trim($header);
        $name = strtolower($header);
        $new->headerNames[$name] = $header;

        if (!is_array($value)) {
            $new->headers[$name] = [trim($value)];
        } else {
            foreach ($value as &$v) {
                $v = trim($v);
            }
            $new->headers[$name] = $value;
        }

        return $new;
    }

    public function withAddedHeader($header, $value)
    {
        if (is_array($value)) {
            $current = array_merge($this->getHeaderLines($header), $value);
        } else {
            $current = $this->getHeaderLines($header);
            $current[] = (string) $value;
        }

        return $this->withHeader($header, $current);
    }

    public function withoutHeader($header)
    {
        if (!$this->hasHeader($header)) {
            return $this;
        }

        $new = clone $this;
        $name = strtolower($header);
        unset($new->headers[$name], $new->headerNames[$name]);
        return $new;
    }

    public function getBody()
    {
        if (!$this->stream) {
            $this->stream = Stream::factory('');
        }

        return $this->stream;
    }

    public function withBody(StreamableInterface $body)
    {
        $new = clone $this;
        $new->stream = $body;
        return $new;
    }

    private function setHeaders(array $headers)
    {
        foreach ($headers as $header => $value) {
            $header = trim($header);
            $name = strtolower($header);
            $this->headerNames[$name] = $header;
            if (!is_array($value)) {
                $this->headers[$name] = [trim($value)];
            } else {
                foreach ($value as &$v) {
                    $v = trim($v);
                }
                $this->headers[$name] = $value;
            }
        }
    }
}
