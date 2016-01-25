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
    // Approved URLs
    private $approved = array();

    // Duplicate URLs
    private $duplicate = array();

    // Status
    private $filtered = false;

    // Clean-Param set
    private $cleanParam = array();

    // URL set
    private $urls = array();
    private $urlsParsed = array();

    // Statistics
    private $statistics = array();

    // URL parameter prefix
    private $paramPrefix = ['?', '&'];

    // URL parameter infix
    private $delimiter = '=';

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
        return $parsed['path'] . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
    }

    /**
     * URL encoder according to RFC 3986
     * Returns a string containing the encoded URL with disallowed characters converted to their percentage encodings.
     * @link http://publicmind.in/blog/url-encoding/
     *
     * @param string $url
     * @return string string
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
        $this->sortURLs();
        $this->stripHashtag();
        $this->filterCleanParam();

        $this->approved = $this->urlsParsed;
        $this->filtered = true;
    }

    private function sortURLs()
    {
        $new = array();
        foreach ($this->urlsParsed as $url) {
            $tmp = parse_url($url);
            if (isset($tmp['query'])) {
                $qPieces = explode('&', $tmp['query']);
                sort($qPieces);
                $tmp['query'] = implode('&', $qPieces);
            }
            $new[] = $this->unParse_url($tmp);
        }
        sort($new);
        $this->urlsParsed = $new;
    }

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
     * Strip hashtag
     *
     * @return string
     */
    private function stripHashtag()
    {
        $new = array();
        foreach ($this->urlsParsed as $url) {
            $new[] = explode('#', $url, 1)[0];
        }
        $this->urlsParsed = $new;
    }

    private function filterCleanParam()
    {
        $new = array();
        $reRun = true;
        while ($reRun) {
            $reRun = false;
            foreach ($this->urlsParsed as $url) {
                $paramArray = $this->getUsedCleanParam($url);
                $combo = $this->generateCombinations($paramArray);
                foreach ($combo as $possibillity) {
                    $tmp = $this->stripParam($url, $possibillity);
                    if (in_array($tmp, $this->urlsParsed)) {
                        $reRun = true;
                        $new[] = $tmp;
                        break;
                    }
                }
                if (!$reRun) {
                    $new[] = $url;
                }
                $this->urlsParsed = array_unique($new);
            }
        }
    }

    private function getUsedCleanParam($url)
    {
        $array = array();
        foreach ($this->cleanParam as $path => $cleanParam) {
            if (!$this->checkBasicRule($path, parse_url($url, PHP_URL_PATH))) {
                continue;
            }
            foreach ($cleanParam as $param) {
                foreach ($this->paramPrefix as $char) {
                    if (strpos($url, $char . $param . $this->delimiter)) {
                        $array[] = $param;
                    }
                }
            }
        }
        return array_unique($array);
    }

    private function checkBasicRule($rule, $path)
    {
        $rule = $this->encode_url($rule);
        // change @ to \@
        $escaped = strtr($rule, array("@" => '\@'));
        // match result
        if (preg_match('@' . $escaped . '@', $path)) {
            if (strpos($escaped, '$') !== false) {
                if (mb_strlen($escaped) - 1 == mb_strlen($path)) {
                    return true;
                }
            } else {
                return true;
            }
        }
        return false;
    }

    private function stripParam($url, $paramArray)
    {
        foreach ($this->cleanParam as $path => $cleanParam) {
            if (!$this->checkBasicRule($path, parse_url($url, PHP_URL_PATH))) {
                continue;
            }
            foreach ($paramArray as $param) {
                if (!in_array($param, $paramArray)) {
                    continue;
                }
                foreach ($this->paramPrefix as $char) {
                    $pos_param = stripos($url, $char . $param . $this->delimiter);
                    $pos_delimiter = stripos($url, "&", min($pos_param + 1, strlen($url)));
                    $len = ($pos_delimiter !== false) ? $pos_delimiter - $pos_param : null;
                    $url = substr($url, $pos_param, $len);
                }
            }
        }
        echo $url;
        return $url;
    }

    /**
     * Lists all duplicate URLs and their replacements
     *
     * @return array
     */
    public function listDuplicates()
    {
        $this->filter();
        return $this->duplicate;
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
        $relative = $this->parseURL($url);
        if ($relative !== false &&
            isset($this->duplicate[$relative])
        ) {
            return $this->duplicate[$relative];
        }
        return false;
    }

    /**
     * Statistics
     *
     * @return array $count
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
     */
    public function addCleanParam($param, $path = '/*')
    {
        $url_encoded = $this->encode_url($path);
        $param_array = explode('&', $param);
        foreach ($param_array as $parameter) {
            $this->cleanParam[$url_encoded][] = $parameter;
        }
    }

    /**
     * Generate all possible alphabetical combinations of given strings
     *
     * @param array $strings - Strings to combine
     * @param array $combinationArray - for internal use only
     * @param int $start - for internal use only
     * @param string $prefix - for internal use only
     * @return array
     */
    private function generateCombinations($strings, &$combinationArray = array(), $start = 0, $prefix = "")
    {
        for ($i = $start; $i < count($strings); $i++) {
            $combination = $prefix . $strings[$i];
            $combinationArray[] = $combination;
            $this->generateCombinations($strings, $combinationArray, ++$start, $combination);
        }
        return $combinationArray;
    }
}
