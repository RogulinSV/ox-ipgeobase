<?php
/**
 * Cache
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 24.12.2014 9:58
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

if (!function_exists('OA_Delivery_Cache_fetch')) {
	require_once MAX_PATH . '/lib/max/Delivery/cache.php';
}

class Cache
{
	/**
	 * @var string
	 */
	private $prefix;

	/**
	 * @var bool
	 */
	private $memorize;

	/**
	 * @var array
	 */
	private static $registry = array();

	/**
	 * @param string $prefix
	 * @param bool $memorize
	 */
	public function __construct($prefix, $memorize = true)
	{
		$this->prefix = (string)$prefix;
		$this->memorize = (bool)$memorize;
	} // function __construct

	/**
	 * Gets data from cache
	 *
	 * @param string $key
	 * @return bool|mixed
	 */
	public function get($key)
	{
		$key = self::hashKey($key);
		if (!$this->memorize or !array_key_exists($key, self::$registry)) {
			$prefix = $GLOBALS['OA_Delivery_Cache']['prefix'];
			$GLOBALS['OA_Delivery_Cache']['prefix'] = $this->prefix;
			$output = OA_Delivery_Cache_fetch($key, $isHash = true);
			$GLOBALS['OA_Delivery_Cache']['prefix'] = $prefix;

			if ($this->memorize && false !== $output) {
				self::$registry[$key] = $output;
			}
		} else {
			$output = self::$registry[$key];
		}

		return $output;
	} // function get

	/**
	 * Sets data to cache
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param int $expire In seconds
	 * @return bool
	 */
	public function set($key, $value, $expire = null)
	{
		$key = self::hashKey($key);
		$prefix = $GLOBALS['OA_Delivery_Cache']['prefix'];
		$GLOBALS['OA_Delivery_Cache']['prefix'] = $this->prefix;
		$output = OA_Delivery_Cache_store($key, $value, $isHash = true, $expire);
		$GLOBALS['OA_Delivery_Cache']['prefix'] = $prefix;
		if ($this->memorize) {
			unset(self::$registry[$key]);
		}

		return $output;
	} // function set

	/**
	 * Checks if cache exists
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has($key)
	{
		return (false !== $this->get($key));
	} // function has

	/**
	 * @param string $key
	 * @return string
	 */
	private static function hashKey($key)
	{
		return md5($key);
	} // function hashKey
}