<?php
/**
 * Class for filtering duplicate URLs according to Yandex Clean-Param specifications.
 *
 * @author VIP nytt (vipnytt@gmail.com)
 * @author Jan-Petter Gundersen (europe.jpg@gmail.com)
 *
 * Project:
 * @link https://github.com/VIPnytt/CleanParam-URL-filter
 * @license https://opensource.org/licenses/MIT MIT license
 *
 * Clean-Param directive specifications:
 * @link https://yandex.com/support/webmaster/controlling-robot/robots-txt.xml#clean-param
 */

namespace vipnytt;

class CleanParamFilter
{
    // Clean-Param set
    private $cleanParam = [];

    // URL set
    private $urls = [];
    private $currentURLs = [];

    // Host set
    private $hosts = [];

    // Status
    private $parsed = false;

    // Approved and duplicate URLs
    private $approved = [];
    private $duplicate = [];

    /**
     * Constructor
     *
     * @param array $urls
     */
    public function __construct($urls)
    {
        // Parse URLs
        foreach ($urls as $url) {
            $url = $this->urlEncode($url);
            $parsed = parse_url($url);
            if ($parsed === false ||
                !isset($parsed['path'])
            ) {
                trigger_error('Invalid URL: ' . $url, E_USER_NOTICE);
                continue;
            }
            if (isset($parsed['host']) && !isset(array_flip($this->hosts)[$parsed['host']])) {
                $this->hosts[] = $parsed['host'];
            }
            $this->urls[] = $url;
        }
    }

    /**
     * URL encoder according to RFC 3986
     * Returns a string containing the encoded URL with disallowed characters converted to their percentage encodings.
     * @link http://publicmind.in/blog/url-encoding/
     *
     * @param string $url
     * @return string
     */
    private static function urlEncode($url)
    {
        $reserved = array(
            ":" => '!%3A!ui',
            "/" => '!%2F!ui',
            "?" => '!%3F!ui',
            "#" => '!%23!ui',
            "[" => '!%5B!ui',
            "]" => '!%5D!ui',
            "@" => '!%40!ui',
            "!" => '!%21!ui',
            "$" => '!%24!ui',
            "&" => '!%26!ui',
            "'" => '!%27!ui',
            "(" => '!%28!ui',
            ")" => '!%29!ui',
            "*" => '!%2A!ui',
            "+" => '!%2B!ui',
            "," => '!%2C!ui',
            ";" => '!%3B!ui',
            "=" => '!%3D!ui',
            "%" => '!%25!ui'
        );
        return preg_replace(array_values($reserved), array_keys($reserved), rawurlencode($url));
    }

    /**
     * Lists all approved URLs
     *
     * @return array
     */
    public function listApproved()
    {
        $this->filter();
        return $this->approved;
    }

    /**
     * Filter URLs
     *
     * @return void
     */
    private function filter()
    {
        // skip the filtering process if it's already done
        if ($this->parsed) {
            return;
        }
        // prepare each individual URL
        foreach ($this->urls as $url) {
            $this->currentURLs[$url] = $this->prepareURL($url);
        }
        // Filter
        $this->filterDuplicates();
        // Sort the result arrays
        sort($this->approved);
        sort($this->duplicate);
        // cleanup
        $this->currentURLs = [];
    }

    /**
     * Prepare URL
     *
     * @param string $url
     * @return string
     */
    private function prepareURL($url)
    {
        $parsed = parse_url($url);
        // sort URL parameters alphabetically
        if (isset($parsed['query'])) {
            $qPieces = explode('&', $parsed['query']);
            sort($qPieces);
            $parsed['query'] = implode('&', $qPieces);
        }
        // remove port number if needless
        if (isset($parsed['port']) && isset($parsed['scheme'])) {
            $defaultPort = getservbyname($parsed['scheme'], 'tcp');
            if (is_int($defaultPort) && $parsed['port'] == $defaultPort) {
                // port number identical to scheme port default.
                $parsed['port'] = null;
            }
        }
        return $this->unParseURL($parsed);
    }

    /**
     * Build URL from array
     *
     * @param array $parsedURL
     * @return string
     */
    private function unParseURL($parsedURL)
    {
        $scheme = isset($parsedURL['scheme']) ? $parsedURL['scheme'] . '://' : '';
        $host = isset($parsedURL['host']) ? $parsedURL['host'] : '';
        $port = isset($parsedURL['port']) ? ':' . $parsedURL['port'] : '';
        $path = isset($parsedURL['path']) ? $parsedURL['path'] : '';
        $query = isset($parsedURL['query']) ? '?' . $parsedURL['query'] : '';
        return "$scheme$host$port$path$query";
    }

    /**
     * Filter duplicate URLs
     *
     * @return void
     */
    private function filterDuplicates()
    {
        $new = [];
        // loop until all duplicates is filtered
        for ($count = 0; $count <= 1; $count++) {
            // for each URL
            foreach ($this->currentURLs as $url => $sorted) {
                $params = $this->findCleanParam($sorted);
                $selected = $this->stripParam($sorted, $params);
                // Check against already checked URLs
                foreach ($new as $random) {
                    $random = $this->stripParam($random, $params);
                    if ($selected === $random) {
                        // URL is duplicate
                        continue 2;
                    }
                }
                // URL is not a duplicate, add it
                $new[$url] = $sorted;
                $count = 0;
            }
            // update the list of non-duplicate URLs
            $this->currentURLs = $new;
        }
        // generate lists of URLs for 3rd party usage
        $this->approved = array_keys($this->currentURLs);
        $this->duplicate = array_diff($this->urls, $this->approved);
    }

    /**
     * Find CleanParam parameters in provided URL
     *
     * @param string $url
     * @return array
     */
    private function findCleanParam($url)
    {
        $paramPrefix = ['?', '&'];
        $paramsFound = [];
        $host = parse_url($url, PHP_URL_HOST);
        // check if CleanParam is set for current host
        if (!isset($this->cleanParam[$host])) {
            return [];
        }
        foreach ($this->cleanParam[$host] as $path => $cleanParam) {
            // make sure the path matches
            if (!$this->checkPath($path, parse_url($url, PHP_URL_PATH))) {
                continue;
            }
            foreach ($cleanParam as $param) {
                // check if parameter is found
                foreach ($paramPrefix as $char) {
                    if (strpos($url, $char . $param . '=') !== false) {
                        $paramsFound[] = $param;
                    }
                }
            }
        }
        return $paramsFound;
    }

    /**
     * Check if path matches
     *
     * @param  string $path - Path compare
     * @param  string $prefix - Path prefix
     * @return bool
     */
    private function checkPath($path, $prefix)
    {
        $path = $this->urlEncode($path);
        // change @ to \@
        $escaped = strtr($path, ["@" => '\@']);
        // match result
        if (preg_match('@' . $escaped . '@', $prefix)) {
            return true;
        }
        return false;
    }

    /**
     * Strip provided parameters from URL
     *
     * @param string $url - URL to check
     * @param array $paramArray - parameters to remove
     * @return string
     */
    private function stripParam($url, $paramArray)
    {
        $prefixArray = ['?', '&'];
        foreach ($paramArray as $param) {
            foreach ($prefixArray as $prefix) {
                // get character positions
                $posParam = stripos($url, $prefix . $param . '=');
                $posDelimiter = stripos($url, '&', min($posParam + 1, strlen($url)));
                if ($posParam === false) {
                    // not found
                    continue;
                }
                $len = ($posDelimiter !== false && $posParam < $posDelimiter) ? $posDelimiter - $posParam : strlen($url);
                // stripped URL
                $url = substr_replace($url, '', $posParam, $len);
            }
        }
        // fix any newly caused URL format problems
        $url = $this->fixQueryString($url);
        return $url;
    }

    /**
     * Fix damaged URL query string
     *
     * @param string $url
     * @return string
     */
    private static function fixQueryString($url)
    {
        // if ? is missing, but & exists, switch
        if (strpos($url, '?') === false && strpos($url, '&') !== false) {
            $url = substr_replace($url, '?', strpos($url, '&'), 1);
        }
        // Strip last character too
        $strip = ['&', '?'];
        foreach ($strip as $char) {
            if (substr($url, -1) == $char) {
                $url = substr_replace($url, '', -1);
            }
        }
        return $url;
    }

    /**
     * Lists all duplicate URLs
     *
     * @return array
     */
    public function listDuplicate()
    {
        $this->filter();
        return $this->duplicate;
    }

    /**
     * Add CleanParam
     *
     * @param string $param - parameter(s)
     * @param string $path - path the param is valid for
     * @param string $host - limit to a single hostname
     * @return void
     */
    public function addCleanParam($param, $path = '/', $host = null)
    {
        if (!isset($host) && count($this->hosts) > 1) {
            trigger_error('URLs from multiple hosts used. Missing `host` parameter', E_USER_ERROR);
            return;
        } elseif (!isset($host)) {
            // use host from URLs
            $host = $this->hosts[0];
        }
        $encodedURL = $this->urlEncode($path);
        $paramArray = explode('&', $param);
        foreach ($paramArray as $parameter) {
            $this->cleanParam[$host][$encodedURL][$parameter] = $parameter;
        }
        $this->parsed = false;
    }
}
