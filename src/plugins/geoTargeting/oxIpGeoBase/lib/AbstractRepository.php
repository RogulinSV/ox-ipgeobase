<?php
/**
 * Abstract DB repository
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 24.12.2014 15:16
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

abstract class AbstractRepository implements CacheAwareInterface, LoggerAwareInterface
{
	/**
	 * @var \MDB2_Driver_Common
	 */
	protected $db;

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * @var Logger
	 */
	protected $logger;

	/**
	 * @var Cache
	 */
	protected $cache;

	/**
	 * @return int
	 */
	abstract public function getVersion();

	/**
	 * @param object $db
	 * @param array $config
	 */
	public function __construct(array $config, $db)
	{
		if (!is_a($db, '\\MDB2_Driver_Common') or !is_subclass_of($db, '\\MDB2_Driver_Common')) {
			throw new \InvalidArgumentException('Argument $db must be an instance of Pear \\MDB2_Driver_Common class');
		}
		$this->db = $db;
		$this->config = $config;
	} // function __construct

	/**
	 * Sets logger layer
	 *
	 * @param Logger $logger
	 * @return $this
	 */
	public function setLogger(Logger $logger)
	{
		$this->logger = $logger;

		return $this;
	} // function setLogger

	/**
	 * @return string
	 */
	protected static function getLoggerPrefix()
	{
		return '[' . strtolower(get_called_class()) . ' repository] database error: ';
	} // function getLoggerPrefix

	/**
	 * @param string $error
	 */
	protected function logError($error)
	{
		if ($this->logger) {
			$prefix = static::getLoggerPrefix();
			$this->logger->error($prefix . $error);
		}
		\OA::debug($error, \PEAR_LOG_ERR);
	} // function logError

	/**
	 * Sets cache layer
	 *
	 * @param Cache $cache
	 * @return $this
	 */
	public function setCache(Cache $cache)
	{
		$this->cache = $cache;

		return $this;
	} // function setCache

	/**
	 * Escapes string value for SQL-query
	 *
	 * @param string $value
	 * @return string
	 */
	protected function escape($value)
	{
		$value = $this->db->escape($value, true);
		$value = $this->db->quote($value);

		return $value;
	} // function escape

	/**
	 * Checks if result set is a PEAR-like error
	 *
	 * @param array|\PEAR_Error $rs
	 * @param mixed $default
	 * @return bool|string
	 */
	protected static function error(&$rs, $default = false)
	{
		$error = false;
		if (is_a($rs, 'PEAR_Error') or is_subclass_of($rs, 'PEAR_Error')) {
			$error = $rs->getMessage();
			$rs = $default;
		}

		return $error;
	} // function error

	/**
	 * Prepares key for cache layer
	 *
	 * @param string $prefix
	 * @param string|int ...$args
	 * @return string
	 */
	protected function key($prefix)
	{
		$key = $prefix;
		for ($i = 1, $c = func_num_args(); $i < $c; $i++) {
			$key .= '/' . (string)func_get_arg($i);
		}
		$key .= '/' . $this->getVersion() . '_' . $this->config[Plugin::CONFIG_NAMESPACE]['dbVersion'];

		return $key;
	} // function key

	/**
	 * Extracts class-specific constants using specific prefix
	 *
	 * @param string $prefix
	 * @return array
	 */
	protected static function constants($prefix = '')
	{
		$output = array();
		$reflection = new \ReflectionClass(get_called_class());
		foreach ($reflection->getConstants() as $name => $value) {
			if (!$prefix or substr($name, 0, strlen($prefix)) === $prefix) {
				$output[] = $value;
			}
		}

		return $output;
	} // function constants
}