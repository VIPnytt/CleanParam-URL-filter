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

use vipnytt\CleanParamFilter\URLParser;

class CleanParamFilter
{
    // Clean-Param set
    private $cleanParam = [];

    // URL set
    private $urls = [];

    // Status
    private $filtered = false;

    // Approved and duplicate URLs
    private $approved = [];
    private $duplicate = [];

    // Invalid URLs
    private $invalid = [];

    /**
     * Constructor
     *
     * @param array $urls
     */
    public function __construct($urls)
    {
        // Parse URLs
        sort($urls);
        foreach ($urls as $url) {
            $urlParser = new URLParser(trim($url));
            if (!$urlParser->isValid()) {
                $this->invalid[] = $url;
                continue;
            }
            $url = $urlParser->encode();
            $this->urls[parse_url($url, PHP_URL_HOST)][] = $url;
        }
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
        if ($this->filtered) {
            return;
        }
        $urlsByHost = [];
        $parsed = [];
        // Loop
        foreach ($this->urls as $host => $urlArray) {
            // prepare each individual URL
            foreach ($urlArray as $url) {
                $path = parse_url($url, PHP_URL_PATH);
                if (mb_substr($path, -1) == '/') {
                    $path = substr_replace($path, '', -1);
                }
                $urlsByHost[$host][$path][$url] = $this->prepareURL($url);
            }
            // Filter
            foreach ($urlsByHost[$host] as $array) {
                $parsed[] = $this->filterDuplicates($array, $host);
            }
        }
        // generate lists of URLs for 3rd party usage
        $allURLs = call_user_func_array('array_merge', $this->urls);
        $this->approved = call_user_func_array('array_merge', $parsed);
        $this->duplicate = array_diff($allURLs, $this->approved);
        // Sort the result arrays
        sort($this->approved);
        sort($this->duplicate);
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
        $path = isset($parsedURL['path']) ? $parsedURL['path'] : '/';
        $query = isset($parsedURL['query']) ? '?' . $parsedURL['query'] : '';
        return $scheme . $host . $port . $path . $query;
    }

    /**
     * Filter duplicate URLs
     *
     * @param array $array - URLs to filter
     * @param string $host - Hostname
     * @return array
     */
    private function filterDuplicates($array, $host)
    {
        $new = [];
        // loop until all duplicates is filtered
        for ($count = 0; $count <= 1; $count++) {
            // for each URL
            foreach ($array as $url => $sorted) {
                $params = $this->findCleanParam($sorted, $host);
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
            $array = $new;
        }
        return array_keys($array);
    }

    /**
     * Find CleanParam parameters in provided URL
     *
     * @param string $url
     * @param string $host
     * @return array
     */
    private function findCleanParam($url, $host)
    {
        $paramPrefix = ['?', '&'];
        $paramsFound = [];
        // check if CleanParam is set for current host
        if (!isset($this->cleanParam[$host])) {
            return $paramsFound;
        }
        foreach ($this->cleanParam[$host] as $path => $cleanParam) {
            // make sure the path matches
            if (!$this->checkPath($path, parse_url($url, PHP_URL_PATH))) {
                continue;
            }
            foreach ($cleanParam as $param) {
                // check if parameter is found
                foreach ($paramPrefix as $char) {
                    if (mb_strpos($url, $char . $param . '=') !== false) {
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
        $pathParser = new URLParser($path);
        $path = $pathParser->encode();
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
                $posParam = mb_stripos($url, $prefix . $param . '=');
                $posDelimiter = mb_stripos($url, '&', min($posParam + 1, mb_strlen($url)));
                if ($posParam === false) {
                    // not found
                    continue;
                }
                $len = ($posDelimiter !== false && $posParam < $posDelimiter) ? $posDelimiter - $posParam : mb_strlen($url);
                // stripped URL
                $url = substr_replace($url, '', $posParam, $len);
            }
        }
        // fix any newly caused URL format problems
        $url = $this->fixURL($url);
        return $url;
    }

    /**
     * Fix damaged URL query string
     *
     * @param string $url
     * @return string
     */
    private static function fixURL($url)
    {
        // if ? is missing, but & exists, switch
        if (mb_strpos($url, '?') === false && mb_strpos($url, '&') !== false) {
            $url = substr_replace($url, '?', mb_strpos($url, '&'), 1);
        }
        // Strip last character
        $strip = ['&', '?', '/'];
        foreach ($strip as $char) {
            if (mb_substr($url, -1) == $char) {
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
     * Lists all invalid URLs
     *
     * @return array
     */
    public function listInvalid()
    {
        return $this->invalid;
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
        if (!isset($host) && count($this->urls) > 1) {
            trigger_error("Missing host parameter for param `$param`. Required because of URLs from multiple hosts is being filtered.", E_USER_WARNING);
            return;
        } elseif (!isset($host)) {
            // use host from URLs
            $host = key($this->urls);
        }
        $urlParser = new URLParser($path);
        $encodedPath = $urlParser->encode();
        $paramArray = explode('&', $param);
        foreach ($paramArray as $parameter) {
            $this->cleanParam[$host][$encodedPath][$parameter] = $parameter;
        }
        $this->filtered = false;
    }
}
