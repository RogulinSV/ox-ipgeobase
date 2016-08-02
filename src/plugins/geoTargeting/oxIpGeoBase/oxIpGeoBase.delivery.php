<?php
/**
 * Plugin delivery functions
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 10.12.2014 10:48
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

use OX\plugins\geoTargeting\oxIpGeoBase\lib\Plugin;

require_once dirname(__FILE__) . '/oxIpGeoBase.autoload.php';
setupIncludePath();

/**
 * Gets geo data
 *
 * @param bool $useCookie
 * @return array
 * @internal the name of this function is important
 */
function Plugin_geoTargeting_delivery($useCookie = false)
{
	$config = $GLOBALS['_MAX']['CONF'];
	$ip = $_SERVER['REMOTE_ADDR'];

	return Plugin::init($config)->delivery($ip, $useCookie);
} // function Plugin_geoTargeting_delivery


/**
 * Overwriting default limitations
 *
 * @return array
 */
function Plugin_geoTargeting_limitations()
{
	if (!isset($GLOBALS['_MAX']['_GEOCACHE']['region'])) {
		require MAX_PATH . '/plugins/deliveryLimitations/Geo/Region.res.inc.php';
	}

	$config = $GLOBALS['_MAX']['CONF'];
	$limitations =& $GLOBALS['_MAX']['_GEOCACHE'];

	return Plugin::init($config)->limitations($limitations);
} // function Plugin_geoTargeting_limitations