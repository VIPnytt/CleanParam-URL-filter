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
    // Directive name
    const DIRECTIVE = 'clean-param';

    // Max rule length
    const MAX_LENGTH = 500;

    // Clean-Param set
    private $cleanParam = [];

    // URL set
    private $urls = [];
    private $urls_wip = [];

    // Approved and duplicate URLs
    private $approved = [];
    private $duplicate = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        /**
         * Coming soon...
         *
         * + Add an array of CleanParams
         * + Add an array of URLs
         */
    }

    /**
     * Add URL(s)
     *
     * @param array|string $url
     * @return void
     */
    public function addURL($url)
    {
        switch (gettype($url)) {
            case 'array':
                foreach ($url as $value) {
                    $this->prepareURL($value);
                }
                break;
            case 'string':
                $this->prepareURL($url);
                break;
        }
    }

    /**
     * Prepare and add URL
     *
     * @param string $url
     * @return void
     */
    private function prepareURL($url)
    {
        $relative = $this->parseURL($url);
        if ($relative === false) {
            trigger_error('Invalid URL: ' . $url, E_USER_NOTICE);
            return;
        }
        $this->urls[] = $relative;
    }

    /**
     * Parse URL
     *
     * @param  string $url
     * @return string|false
     */
    private function parseURL($url)
    {
        $url = $this->encode_url($url);
        $parsed = parse_url($url);
        if ($parsed === false ||
            !isset($parsed['path'])
        ) {
            return false;
        }
        return $this->unParse_url($parsed);
    }

    /**
     * URL encoder according to RFC 3986
     * Returns a string containing the encoded URL with disallowed characters converted to their percentage encodings.
     * @link http://publicmind.in/blog/url-encoding/
     *
     * @param string $url
     * @return string
     */
    private static function encode_url($url)
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
        $url = preg_replace(array_values($reserved), array_keys($reserved), rawurlencode($url));
        return $url;
    }

    /**
     * Build URL from array
     * Does the opposite of the parse_url($string) function
     *
     * @param array $parsed_url
     * @return string
     */
    private function unParse_url($parsed_url)
    {
        $scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
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
        if (count($this->urls) === count($this->urls_wip)) {
            return;
        }
        $this->convert();
        $this->filterDuplicateParam();
        sort($this->approved);
        sort($this->duplicate);
    }

    /**
     * Convert URLs to a parser readable format
     *
     * @return void
     */
    private function convert()
    {
        foreach ($this->urls as $url) {
            $tmp = $url;
            $tmp = $this->stripFragmentSingle($tmp);
            $tmp = $this->fixBrokenQuery($tmp);
            $tmp = $this->paramSort($tmp);
            $this->urls_wip[$url] = $tmp;
        }
    }

    /**
     * Strip URL fragment
     *
     * @param string $url
     * @return string
     */
    private static function stripFragmentSingle($url)
    {
        return explode('#', $url, 1)[0];
    }

    /**
     * Fix broken URL query string
     *
     * @param $url
     * @return string
     */
    private static function fixBrokenQuery($url)
    {
        if (strpos($url, '?') === false && strpos($url, '&') !== false) {
            $url = substr_replace($url, '?', strpos($url, '&'), 1);
        }
        $strip = ['&', '?'];
        foreach ($strip as $char) {
            if (substr($url, -1) == $char) {
                $url = substr_replace($url, '', -1);
            }
        }
        return $url;
    }

    /**
     * Sort the URL parameters alphabetically
     *
     * @param string $url
     * @return string
     */
    private function paramSort($url)
    {
        $parsed = parse_url($url);
        if (isset($parsed['query'])) {
            $qPieces = explode('&', $parsed['query']);
            sort($qPieces);
            $parsed['query'] = implode('&', $qPieces);
        }
        return $this->unParse_url($parsed);
    }

    /**
     * Filter duplicate URLs
     *
     * @return void
     */
    private function filterDuplicateParam()
    {
        $urls_new = [];
        for ($count = 0; $count <= 1; $count++) {
            foreach ($this->urls_wip as $url => $url_sorted) {
                $params = $this->findCleanParam($url_sorted);
                $selected = $this->stripParam($url_sorted, $params);
                foreach ($urls_new as $random) {
                    $random = $this->stripParam($random, $params);
                    if ($selected === $random) {
                        continue 2;
                    }
                }
                $urls_new[$url] = $url_sorted;
                $count = 0;
            }
            $this->urls_wip = $urls_new;
        }
        $this->approved = array_keys($this->urls_wip);
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
        foreach ($this->cleanParam as $path => $cleanParam) {
            if (!$this->checkPath($path, parse_url($url, PHP_URL_PATH))) {
                continue;
            }
            foreach ($cleanParam as $param) {
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
        $path = $this->encode_url($path);
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
     * @param $url - URL to check
     * @param $paramArray - parameters to remove
     * @return string
     */
    private function stripParam($url, $paramArray)
    {
        $prefixArray = ['?', '&'];
        foreach ($paramArray as $param) {
            foreach ($prefixArray as $prefix) {
                $pos_param = stripos($url, $prefix . $param . '=');
                $pos_delimiter = stripos($url, '&', min($pos_param + 1, strlen($url)));
                if ($pos_param === false) {
                    continue;
                }
                $len = ($pos_delimiter !== false && $pos_param < $pos_delimiter) ? $pos_delimiter - $pos_param : strlen($url);
                $url = substr_replace($url, '', $pos_param, $len);
            }
        }
        return $this->fixBrokenQuery($url);
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
     * @param string $param
     * @param string $path
     * @return void
     */
    public function addCleanParam($param, $path = '/')
    {
        $url_encoded = $this->encode_url($path);
        if ($this->checkRuleLength($param, $url_encoded) === false) return;
        $param_array = explode('&', $param);
        foreach ($param_array as $parameter) {
            $this->cleanParam[$url_encoded][$parameter] = $parameter;
        }
    }

    /**
     * Rule length check
     *
     * @param $param
     * @param $path
     * @return bool
     */
    private function checkRuleLength($param, $path = '/')
    {
        if (strlen(self::DIRECTIVE . ": $param $path") > self::MAX_LENGTH) {
            trigger_error(self::DIRECTIVE . ' rule too long, hereby ignored.', E_USER_WARNING);
            return false;
        }
        return true;
    }
}
