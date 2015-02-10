<?php
/**
 * Task progress
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 30.01.2015 15:46
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

class TaskProgress
{
	/**
	 * @var bool
	 */
	private $writable;

	/**
	 * @var bool
	 */
	private $readable;

	/**
	 * @return string
	 */
	private function getSourcePath()
	{
		return MAX_PATH . '/var/PROGRESS';
	} // function getSourcePath

	/**
	 * @param string $source
	 * @return bool
	 */
	private function isWriteable($source)
	{
		if (is_null($this->writable)) {
			if (!file_exists($source)) {
				$source = dirname($source);
			}
			$this->writable = is_writeable($source);
		}

		return $this->writable;
	} // function isWriteable

	/**
	 * @param string $source
	 * @return bool
	 */
	private function isReadable($source)
	{
		if (is_null($this->readable)) {
			$this->readable = (file_exists($source) && is_readable($source));
		}

		return $this->readable;
	} // function isReadable

	/**
	 * @param int $process
	 * @param float $percent
	 * @return bool
	 */
	public function write($process, $percent)
	{
		$output = false;
		$source = $this->getSourcePath();
		if ($this->isWriteable($source)) {
			$content = sprintf('[%011s] %d%%', $process, $percent);
			$output = (bool)file_put_contents($source, $content);
		}

		return $output;
	} // function write

	/**
	 * @param int $process
	 * @return null|int
	 */
	public function read($process)
	{
		$output = null;
		$source = $this->getSourcePath();
		if ($this->isReadable($source) && !!($content = file_get_contents($source))) {
			$process = sprintf('[%011s]', $process);
			if (substr($content, 0, strlen($process)) === $process) {
				$output = (float)trim(substr($content, strlen($process)));
			}
		}

		return $output;
	} // function read
}