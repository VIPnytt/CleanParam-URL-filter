<?php
/**
 * URL parser
 *
 * @author Jan-Petter Gundersen (europe.jpg@gmail.com)
 */

namespace vipnytt\CleanParamFilter;

final class URLParser
{
    private $url;

    /**
     * Constructor
     *
     * @param string $url
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * Validate URL
     *
     * @return bool
     */
    public function isValid()
    {
        $this->encode();
        $parsed = parse_url($this->url);
        if ($parsed === false
            || !$this->isHostValid()
            || !$this->isSchemeValid()
        ) {
            return false;
        }
        return true;
    }

    /**
     * URL encoder according to RFC 3986
     * Returns a string containing the encoded URL with disallowed characters converted to their percentage encodings.
     * @link http://publicmind.in/blog/url-encoding/
     *
     * @return string string
     */
    public function encode()
    {
        $reserved = [
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
        ];
        return $this->url = preg_replace(array_values($reserved), array_keys($reserved), rawurlencode($this->url));
    }

    /**
     * Validate host name
     *
     * @link http://stackoverflow.com/questions/1755144/how-to-validate-domain-name-in-php
     * @return bool
     */
    public function  isHostValid()
    {
        $host = parse_url($this->url, PHP_URL_HOST);
        return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $host) //valid chars check
            && preg_match("/^.{1,253}$/", $host) //overall length check
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $host) //length of each label
            && !filter_var($host, FILTER_VALIDATE_IP)); //is not an IP address
    }

    /**
     * Validate URL scheme
     *
     * @return bool
     */
    public function isSchemeValid()
    {
        return in_array(parse_url($this->url, PHP_URL_SCHEME), [
            'http', 'https',
            'ftp', 'sftp'
        ]);
    }
}
