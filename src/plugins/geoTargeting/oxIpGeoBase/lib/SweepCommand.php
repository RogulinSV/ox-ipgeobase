<?php
/**
 * Command "sweep"
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 03.02.2015 15:15
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

class SweepCommand implements LoggerAwareInterface
{
	/**
	 * @var array
	 */
	private $task;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var array
	 */
	private $errors = array();

	/**
	 * @param array $task
	 */
	public function __construct(array $task)
	{
		$this->task = $task;
	} // function __construct

	/**
	 * @param array $task
	 * @return SweepCommand
	 */
	public static function init(array $task)
	{
		return new self($task);
	} // function init

	/**
	 * @param Logger $logger
	 * @return $this
	 */
	public function setLogger(Logger $logger)
	{
		$this->logger = $logger;

		return $this;
	} // function setLogger

	/**
	 * @param string $error
	 */
	private function logError($error)
	{
		$this->errors[] = $error;
		if ($this->logger) {
			$this->logger->error($error);
		}
	} // function logError

	/**
	 * @return bool
	 */
	public function hasErrors()
	{
		return (count($this->errors) > 0);
	} // function hasErrors

	/**
	 * @return void
	 */
	private function flushErrors()
	{
		$this->errors = array();
	} // function flushErrors

	/**
	 * @param string $message
	 */
	private function logDebug($message)
	{
		if ($this->logger) {
			$this->logger->info($message);
		}
	} // function debug

	/**
	 * @return bool
	 */
	public function run()
	{
		$this->flushErrors();

		if (!empty($this->task['file_location'])) {
			if (!Filesystem::isSafePath($this->task['file_location'])) {
				$this->logError('temporary file is in an unsafe path, sweeping of the temporary file is canceled');
			} else if (!file_exists($this->task['file_location']) or !is_file($this->task['file_location'])) {
				$this->logError(sprintf('temporary file %s is not exists, sweeping of the temporary file is canceled', $this->task['file_location']));
			} else if (!unlink($this->task['file_location'])) {
				$this->logError(sprintf('unable to delete temporary file %s', $this->task['file_location']));
			} else {
				$this->logDebug(sprintf('temporary file %s successfully deleted', $this->task['file_location']));
			}
		} // if

		return !$this->hasErrors();
	} // function run
}