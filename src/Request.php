<?php
namespace GuzzleHttp\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamableInterface;
use Psr\Http\Message\UriInterface;

/**
 * PSR-7 request implementation.
 */
class Request implements RequestInterface
{
    use MessageTrait;

    /** @var string */
    private $method;

    /** @var null|string */
    private $requestTarget;

    /** @var null|UriInterface */
    private $uri;

    /**
     * @param null|string $uri URI for the request.
     * @param null|string $method HTTP method for the request.
     * @param string|resource|StreamableInterface $body Message body.
     * @param array  $headers Headers for the message.
     * @param string $protocolVersion HTTP protocol version.
     *
     * @throws InvalidArgumentException for an invalid URI
     */
    public function __construct(
        $method,
        $uri,
        array $headers = [],
        $body = null,
        $protocolVersion = '1.1'
    ) {
        if (is_string($uri)) {
            $uri = new Uri($uri);
        } elseif (!($uri instanceof UriInterface)) {
            throw new \InvalidArgumentException(
                'URI must be a string or Psr\Http\Message\UriInterface'
            );
        }

        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocol = $protocolVersion;
        $this->updateHostFromUri();

        if ($body) {
            $this->stream = Stream::factory($body);
        }
    }

    public function getRequestTarget()
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if ($this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    public function withRequestTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException(
                'Invalid request target provided; cannot contain whitespace'
            );
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function withMethod($method)
    {
        $new = clone $this;
        $new->method = strtoupper($method);
        return $new;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri)
    {
        $new = clone $this;
        $new->uri = $uri;
        $new->updateHostFromUri();
        return $new;
    }

    private function updateHostFromUri()
    {
        // Set a default host header if one is not present.
        if ($host = $this->uri->getHost()) {
            $this->headerNames['host'] = 'Host';
            $this->headers['host'] = [$host];
        }
    }
}
