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

use vipnytt\CleanParamFilter\extensions;

class CleanParamFilter
{
	// Approved URLs
	protected $approved = array();

	// Duplicate URLs
	protected $duplicate = array();

	// Status
	private $filtered = false;

	// Clean-Param set
	private $cleanParam = array();

	// URL set
	private $urls = array();

	// Statistics
	private $statistics = array();

	/**
	 * Constructor
	 *
	 * @param array|string $cleanParam
	 * @param array|string $urls
	 */
	public function __construct($cleanParam, $urls = null)
	{
		$this->addCleanParam($cleanParam);
		$this->addURL($urls);
	}

	/**
	 * Add CleanParam
	 *
	 * @param array|string $cleanParam
	 */
	public function addCleanParam($cleanParam)
	{
		switch (gettype($cleanParam)) {
			case 'array':
				$this->addCleanParamArray($cleanParam);
				break;
			case 'string':
				$this->addCleanParam($this->initialize_robots_txt_parser($cleanParam));
				break;
		}
	}

	/**
	 * Add Clean-Param array
	 *
	 * @param array $array
	 */
	private function addCleanParamArray($array)
	{
		//$this->cleanParam = array_merge($array, $this->cleanParam);
		foreach ($array as $key => $value) {
			if (gettype($key) != 'string' ||
				gettype($value) != 'array'
			) {
				// TODO: trigger error, unknown format
				return;
			}
			// $key = URL
			// $value = parameter(s)
			// OR
			// $key = parameter(s)
			// $value = URL
			if (empty($value)) {
				$value = array('/*');
			}
		}
	}

	public static function getParserExtensions()
	{
		$extensions = array();
		$files = glob(dirname(__FILE__) . '/*.inc.php', GLOB_BRACE);
		if ($files !== false) {
			foreach ($files as $file) {
				include_once($file);
				$class = rtrim($file, ".inc.php");
				if (method_exists($class, 'isInstalled')
					&& is_callable(array($class, 'isInstalled'))
					&& $class->isInstalled()
				) {
					$extensions[] = $class;
				}
			}
		}
		return $extensions;
	}

	/**
	 * Robots.txt parser
	 *
	 * @param string $content - robots.txt valid content
	 * @return array - [URL]=param or [param]=URL
	 */
	private function initialize_robots_txt_parser($content)
	{
		$extensions = $this->getParserExtensions();
		foreach ($extensions as $parser) {
			$cleanParam = new $parser->parse($content);
			if (is_array($cleanParam)) {
				return $cleanParam;
			}
		}
		trigger_error("Unable to parse Clean-Param string, no supported robots.txt parsers installed, please use pre-parsed array instead", E_USER_WARNING);
		return array();
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
				$this->parseURLArray($url);
				break;
			case 'string':
				$this->prepareURL($url);
				break;
		}
	}

	/**
	 * Parse URL array
	 *
	 * @param array $array
	 */
	private function parseURLArray($array)
	{
		foreach ($array as $url) {
			$this->addURL($url);
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
			return;
		}
		$this->urls[] = $relative;
		$this->filtered = false;
	}

	/**
	 * Parse single URL
	 *
	 * @param  string $url
	 * @return string|false
	 */
	private function parseURL($url)
	{
		$parsed = parse_url($url);
		if ($parsed === false ||
			!isset($parsed['path'])
		) {
			return false;
		}
		return $parsed['path'] . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
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
		$this->optimize();
		//TODO: Filter
		$this->filtered = true;
	}

	/**
	 * Optimize URL and Clean-Param arrays
	 *
	 * @return void
	 */
	private function optimize()
	{
		$this->cleanParam = array_unique($this->cleanParam);
		$this->urls = array_unique($this->urls);
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
}
