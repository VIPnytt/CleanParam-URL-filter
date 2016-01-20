<?php
/**
 * t1gor/Robots.txt-Parser-Class driver
 * for VIPnytt/CleanParam-URL-filter
 *
 * Robots.txt parser:
 * @link https://github.com/t1gor/Robots.txt-Parser-Class
 */

namespace vipnytt\CleanParamFilter\extensions;


class robots_txt_parser__t1gor
{
	private $class = 'RobotsTxtParser';
	private $cleanParam = 'getCleanParam';

	public function isInstalled()
	{
		return (method_exists($this->class, $this->cleanParam)
			&& is_callable(array($this->class, $this->cleanParam))
		);
	}

	/**
	 * @param $content - robots.txt content
	 * @return array|bool
	 */
	public function parse($content)
	{
		$parser = new $this->class($content);
		return $parser->$this->cleanParam();
	}
}