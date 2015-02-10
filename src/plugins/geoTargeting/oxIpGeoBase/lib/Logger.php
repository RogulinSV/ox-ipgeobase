<?php
/**
 * PSR-0 compatible logger
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 23.12.2014 16:40
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

class Logger
{
	/**
	 * @var \Log
	 */
	private $logger;

	/**
	 * @param object $logger
	 */
	public function __construct($logger)
	{
		if (!is_a($logger, '\\Log') && !is_subclass_of($logger, '\\Log')) {
			throw new \InvalidArgumentException('Argument $logger must be an instance of Pear \\Log class');
		}
		$this->logger = $logger;
	} // function __construct

	/**
	 * Logs with an arbitrary level
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 */
	public function log($level, $message, array $context = array())
	{
		$this->logger->log($message, $level);
	} // function log

	/**
	 * Logging detailed debug information
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function debug($message, array $context = array())
	{
		$this->logger->log($message, PEAR_LOG_DEBUG);
	} // function debug

	/**
	 * Logging record for debugging
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function info($message, array $context = array())
	{
		$this->logger->log($message, PEAR_LOG_INFO);
	} // function message

	/**
	 * Logging normal but significant events
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function notice($message, array $context = array())
	{
		$this->logger->log($message, PEAR_LOG_NOTICE);
	} // function notice

	/**
	 * Logging exceptional occurrences that are not errors
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function warning($message, array $context = array())
	{
		$this->logger->log($message, PEAR_LOG_WARNING);
	} // function warning

	/**
	 * Logging runtime errors that do not require immediate action but should typically be logged and monitored
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function error($message, array $context = array())
	{
		$this->logger->log($message, PEAR_LOG_ERR);
	} // function error

	/**
	 * Logging critical conditions
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function critical($message, array $context = array())
	{
		$this->logger->log($message, PEAR_LOG_CRIT);
	} // function critical

	/**
	 * Logging events that`s must be taken immediately
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function alert($message, array $context = array())
	{
		$this->logger->log($message, PEAR_LOG_ALERT);
	} // function alert
}