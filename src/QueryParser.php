<?php
namespace GuzzleHttp\Psr7;

/**
 * Parses query strings into an hash containing information about the query
 * string, including:
 *
 * - data: Query string data
 * - duplicates: true/false if duplicate keys of the same name were present.
 * - numeric_indices: true/false if duplicate keys use numeric indices.
 *
 * @internal Use Query::fromString()
 */
class QueryParser
{
    private $duplicates;
    private $numericIndices;

    /**
     * Parse a query string into an associative array.
     *
     * @param string      $str         Query string to parse
     * @param bool|string $urlEncoding How the query string is encoded
     *
     * @return array
     */
    public function parse($str, $urlEncoding = true)
    {
        if ($str === '') {
            return ['data' => [], 'duplicates' => false, 'numeric_indices' => false];
        }

        $result = [];
        $this->duplicates = false;
        $this->numericIndices = true;
        $decoder = self::getDecoder($urlEncoding);

        foreach (explode('&', $str) as $kvp) {

            $parts = explode('=', $kvp, 2);
            $key = $decoder($parts[0]);
            $value = isset($parts[1]) ? $decoder($parts[1]) : null;

            // Special handling needs to be taken for PHP nested array syntax
            if (strpos($key, '[') !== false) {
                $this->parsePhpValue($key, $value, $result);
                continue;
            }

            if (!isset($result[$key])) {
                $result[$key] = $value;
            } else {
                $this->duplicates = true;
                if (!is_array($result[$key])) {
                    $result[$key] = [$result[$key]];
                }
                $result[$key][] = $value;
            }
        }

        return [
            'data'            => $result,
            'duplicates'      => $this->duplicates,
            'numeric_indices' => $this->numericIndices
        ];
    }

    /**
     * Returns a callable that is used to URL decode query keys and values.
     *
     * @param string|bool $type One of true, false, RFC3986, and RFC1738
     *
     * @return callable|string
     */
    private static function getDecoder($type)
    {
        if ($type === true) {
            return function ($value) {
                return rawurldecode(str_replace('+', ' ', $value));
            };
        } elseif ($type == PHP_QUERY_RFC3986) {
            return 'rawurldecode';
        } elseif ($type == PHP_QUERY_RFC1738) {
            return 'urldecode';
        } else {
            return function ($str) { return $str; };
        }
    }

    /**
     * Parses a PHP style key value pair.
     *
     * @param string      $key    Key to parse (e.g., "foo[a][b]")
     * @param string|null $value  Value to set
     * @param array       $result Result to modify by reference
     */
    private function parsePhpValue($key, $value, array &$result)
    {
        $node =& $result;
        $keyBuffer = '';

        for ($i = 0, $t = strlen($key); $i < $t; $i++) {
            switch ($key[$i]) {
                case '[':
                    if ($keyBuffer) {
                        $this->prepareNode($node, $keyBuffer);
                        $node =& $node[$keyBuffer];
                        $keyBuffer = '';
                    }
                    break;
                case ']':
                    $k = $this->cleanKey($node, $keyBuffer);
                    $this->prepareNode($node, $k);
                    $node =& $node[$k];
                    $keyBuffer = '';
                    break;
                default:
                    $keyBuffer .= $key[$i];
                    break;
            }
        }

        if (isset($node)) {
            $this->duplicates = true;
            $node[] = $value;
        } else {
            $node = $value;
        }
    }

    /**
     * Prepares a value in the array at the given key.
     *
     * If the key already exists, the key value is converted into an array.
     *
     * @param array  $node Result node to modify
     * @param string $key  Key to add or modify in the node
     */
    private function prepareNode(&$node, $key)
    {
        if (!isset($node[$key])) {
            $node[$key] = null;
        } elseif (!is_array($node[$key])) {
            $node[$key] = [$node[$key]];
        }
    }

    /**
     * Returns the appropriate key based on the node and key.
     */
    private function cleanKey($node, $key)
    {
        if ($key === '') {
            $key = $node ? (string) count($node) : 0;
            // Found a [] key, so track this to ensure that we disable numeric
            // indexing of keys in the resolved query aggregator.
            $this->numericIndices = false;
        }

        return $key;
    }
}
