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
	 * @param string $prefix
	 */
	public function __construct($prefix)
	{
		$this->prefix = (string)$prefix;
	} // function __construct

	/**
	 * Gets data from cache
	 *
	 * @param string $key
	 * @return bool|mixed
	 */
	public function get($key)
	{
		$prefix = $GLOBALS['OA_Delivery_Cache']['prefix'];
		$GLOBALS['OA_Delivery_Cache']['prefix'] = $this->prefix;
		$output = OA_Delivery_Cache_fetch($key);
		$GLOBALS['OA_Delivery_Cache']['prefix'] = $prefix;

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
		$prefix = $GLOBALS['OA_Delivery_Cache']['prefix'];
		$GLOBALS['OA_Delivery_Cache']['prefix'] = PLUGIN_IPGEOBASE_CACHE_PREFIX;
		$output = (false === OA_Delivery_Cache_fetch($key) && OA_Delivery_Cache_store($key, $value, false, $expire));
		$GLOBALS['OA_Delivery_Cache']['prefix'] = $prefix;

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
}