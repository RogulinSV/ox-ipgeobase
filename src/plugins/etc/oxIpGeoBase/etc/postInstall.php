<?php
/**
 * Post install actions
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 27.01.2015 10:42
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

use OX\plugins\geoTargeting\oxIpGeoBase\lib\Plugin;

require_once MAX_PATH . '/plugins/geoTargeting/oxIpGeoBase/oxIpGeoBase.autoload.php';

$className = 'Plugins_GeoTargeting_PostInstall';

class Plugins_GeoTargeting_PostInstall
{
	/**
	 * Runs post install actions
	 *
	 * @return bool
	 */
	public function execute()
	{
		return $this->updateSettings();
	} // function execute

	/**
	 * Updates plugin settings
	 *
	 * @return bool
	 */
	private function updateSettings()
	{
		/** @var Plugins_GeoTargeting_oxIpGeoBase_OxIpGeoBase $component */
		$component = \OX_Component::factory('geoTargeting', Plugin::COMPONENT_GEO_TARGETING_ID);

		return $component->onEnable();
	} // function updateSettings
}
