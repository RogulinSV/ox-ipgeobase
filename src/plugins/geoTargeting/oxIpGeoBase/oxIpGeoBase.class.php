<?php
/**
 * Default component class
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 10.12.2014 10:29
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

use OX\plugins\geoTargeting\oxIpGeoBase\lib\Plugin;

require_once dirname(__FILE__) . '/oxIpGeoBase.autoload.php';

/**
 * Class Plugins_GeoTargeting_oxIpGeoBase_OxIpGeoBase
 */
class Plugins_GeoTargeting_oxIpGeoBase_OxIpGeoBase extends OX_Component
{
	/**
	 * @return bool
	 */
	public function onEnable()
	{
		$secret = \MAX_getRandomNumber(32);

		$settings = new OA_Admin_Settings();
		$settings->settingChange(Plugin::CONFIG_NAMESPACE, 'secret', $secret);

		return $settings->writeConfigChange();
	} // function onEnable

	/**
	 * @return bool
	 */
	public function onDisable()
	{
		return true;
	} // function onDisable

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->translate('IpGeoBase Plugin');
	} // function getName

	/**
	 * The method calls to the delivery half of the plugin to get the geo information
	 *
	 * @param bool $useCookie Use cookies for store / retrieve GeoTargeting results
	 * @return array An array that will contain the results of the GeoTargeting lookup.
	 */
	public function getGeoInfo($useCookie = false)
	{
		$output = array();

		if (Plugin::isEnabled()) {
			Plugin_geoTargeting_limitations();
			$output = Plugin_geoTargeting_delivery($useCookie);
		}

		return $output;
	} // function getGeoInfo
}