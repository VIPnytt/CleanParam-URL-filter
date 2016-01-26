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

    // Approved URLs
    private $approved = array();

    // Status
    private $filtered = false;

    // Clean-Param set
    private $cleanParam = array();

    // URL set
    private $urls = array();
    private $urlsParsed = array();

    // Statistics
    private $statistics = array();

    /**
     * Constructor
     */
    public function __construct()
    {
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
        $this->filtered = false;
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
        //return $this->unParse_url($parsed);
        return $parsed['path'] . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
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
        if ($this->filtered) {
            return;
        }
        $this->cleanParam = array_unique($this->cleanParam);
        $this->urls = array_unique($this->urls);
        $this->urlsParsed = $this->urls;
        $this->stripFragment();
        $this->filterDuplicateParam();
        $this->approved = $this->urlsParsed;
        $this->filtered = true;
    }

    /**
     * Strip URL fragment hashtag
     *
     * @return string
     */
    private function stripFragment()
    {
        $new = array();
        foreach ($this->urlsParsed as $url) {
            $new[] = explode('#', $url, 1)[0];
        }
        $this->urlsParsed = $new;
    }

    /**
     * Filter duplicate URLs
     *
     * @return void
     */
    private function filterDuplicateParam()
    {
        $urlsParsed_replacement = array();
        for ($i = 0; $i <= 1; $i++) {
            foreach ($this->urlsParsed as $url) {
                $paramArray = $this->getCleanParamInURL($url);
                $current = $this->paramSort($url);
                $current = $this->stripParam($current, $paramArray);
                foreach ($urlsParsed_replacement as $existing) {
                    $existing = $this->paramSort($existing);
                    $existing = $this->stripParam($existing, $paramArray);
                    if ($current === $existing) {
                        continue 2;
                    }
                }
                $urlsParsed_replacement[] = $url;
                $i = 0;
            }
            $this->urlsParsed = array_unique($urlsParsed_replacement);
        }
    }

    /**
     * Get CleanParam parameters found in provided URL
     *
     * @param string $url
     * @return array
     */
    private function getCleanParamInURL($url)
    {
        $paramPrefix = ['?', '&'];
        $array = array();
        foreach ($this->cleanParam as $path => $cleanParam) {
            if (!$this->checkPath($path, parse_url($url, PHP_URL_PATH))) {
                continue;
            }
            foreach ($cleanParam as $param) {
                foreach ($paramPrefix as $char) {
                    if (strpos($url, $char . $param . '=') !== false) {
                        $array[] = $param;
                    }
                }
            }
        }
        return array_unique($array);
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
        //TODO: Will be broken when switching to full URLs
        $path = $this->encode_url($path);
        // change @ to \@
        $escaped = strtr($path, array("@" => '\@'));
        // match result
        if (preg_match('@' . $escaped . '@', $prefix)) {
            return true;
        }
        return false;
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
        if (substr($url, -1) == '?') {
            $url = substr_replace($url, '', -1);
        }
        return $url;
    }

    /**
     * Check if URL is duplicate
     *
     * @param string $url
     * @return bool
     */
    public function isDuplicate($url)
    {
        $this->filter();
        if ($this->parseURL($url) === false) return false;
        $url = $this->paramSort($url);
        return !in_array($url, $this->urlsParsed);
    }

    /**
     * Statistics
     *
     * @return array
     */
    public function getStatistics()
    {
        $this->filter();
        return $this->statistics;
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
            $this->cleanParam[$url_encoded][] = $parameter;
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
            trigger_error('Clean-Param rule too long, hereby ignored.', E_USER_WARNING);
            return false;
        }
        return true;
    }
}
