<?php
/**
 * Main plugin class
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 23.12.2014 15:35
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

class Plugin
{
	const CACHE_PREFIX = 'ipgeobase_';
	const LOGGER_CHANNEL = 'ipgeobase';
	const CONFIG_NAMESPACE = 'oxIpGeoBase';

	const PLUGIN_ID = 'openXIpGeoBase';
	const COMPONENT_GEO_TARGETING_ID = 'oxIpGeoBase';

	/**
	 * @var array
	 */
	private static $blank = array(
		'country_code'  => '',
		'region'        => '',
		'city'          => '',
		'postal_code'   => '',
		'latitude'      => '',
		'longitude'     => '',
		'dma_code'      => '',
		'area_code'     => '',
		'organisation'  => '',
		'isp'           => '',
		'netspeed'      => ''
	);

	/**
	 * @var array
	 */
	private $config;

	/**
	 * Plugin constructor
	 *
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		$this->config = $config;
	} // function __construct

	/**
	 * Static class generator
	 *
	 * @param array $config
	 * @return Plugin
	 */
	public static function init(array $config)
	{
		return new self($config);
	} // function init

	/**
	 * Gets geo location data for delivery limitations
	 *
	 * @param string $ip
	 * @param bool $useCookie
	 * @return array
	 */
	public function delivery($ip, $useCookie = false)
	{
		// Try and read the data from the geo cookie...
		$cookieName = $this->config['var']['viewerGeo'];
		if ($useCookie && isset($_COOKIE[$cookieName])) {
			return self::unpackCookie($_COOKIE[$cookieName]);
		}

		\OX_Delivery_logMessage('[' . self::LOGGER_CHANNEL . '] geo location delivery for ' . $ip . ' remote addr', 7);

		$output = self::$blank;

		$targeting = Factory::targeting($this->config);
		$regionData = $targeting->findRegionData($ip);
		if ($regionData) {
			$cityId = (int)$regionData['city_id'];
			$cityData = $targeting->getCityData($cityId);
			if ($cityData) {
				$output['country_code'] = $regionData['country_code'];
				$output['region'] = $cityData['region_code'];
				$output['city'] = Utils::toUtf($cityData['city_name']);
				$output['latitude'] = $cityData['latitude'];
				$output['longitude'] = $cityData['longitude'];

				// Store this information in the cookie for later use
				if ($useCookie) {
					\MAX_cookieAdd($cookieName, self::packCookie($output));
				}
			}
		}

		return $output;
	} // function delivery

	/**
	 * Patches delivery limitations for plugin-specific geo location data
	 *
	 * @param array $limitations
	 * @return array
	 * @internal btw, that's must be as standalone plugin
	 */
	public function limitations(array &$limitations)
	{
		$targeting = Factory::targeting($this->config);
		foreach ($targeting->getRegionsList() as $countryCode => $regionsList) {
			if (isset($limitations['region'][$countryCode])) {
				$limitations['region'][$countryCode] = array(
					0 => $limitations['region'][$countryCode][0]
				);
				foreach ($regionsList as $regionCode => $regionName) {
					$limitations['region'][$countryCode][$regionCode] = Utils::toUtf($regionName);
				}
			} // if
		} // foreach

		return $limitations;
	} // function limitations

	/**
	 * @param array $data
	 * @return string
	 */
	private static function packCookie(array $data)
	{
		$data = array_merge(self::$blank, $data);

		return Utils::packData($data);
	} // function packCookie

	/**
	 * @param string $data
	 * @return array
	 */
	private static function unpackCookie($data)
	{
		$data = Utils::unpackData($data);
		$data = array_intersect_key($data, self::$blank);
		$data = array_merge(self::$blank, $data);

		return $data;
	} // function unpackCookie

	/**
	 * Checks status of plugin
	 *
	 * @return bool
	 */
	public static function isEnabled()
	{
		$manager = new \OX_PluginManager();

		return $manager->isEnabled(self::PLUGIN_ID);
	} // function isEnabled
}