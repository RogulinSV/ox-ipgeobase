<?php
/**
 * Autoloader of plugin classes
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 12.01.2015 15:00
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

defined('MAX_PATH') or die('Required constants not defined');

/**
 * Plugin-specific autoloaders
 */
spl_autoload_register(function($class) {
	$namespace = 'OX\\plugins\\geoTargeting\\oxIpGeoBase\\lib\\';
	$length = strlen($namespace);

	if (strlen($class) > $length && $namespace === substr($class, 0, $length)) {
		$filename = MAX_PATH . '/plugins/geoTargeting/oxIpGeoBase/lib/' . str_replace('\\', '/', substr($class, $length)) . '.php';
		if (file_exists($filename)) {
			require_once $filename;
		}
	}
});

spl_autoload_register(function($class) {
	$prefix = 'Plugins_GeoTargeting_';
	$length = strlen($prefix);

	if (strlen($class) > $length && $prefix === substr($class, 0, $length)) {
		$folders = explode('_', substr($class, $length));
		$class = array_pop($folders);
		$folders = array_map(function($folder) {
			return strtolower($folder);
		}, $folders);
		$filename = MAX_PATH . '/www/admin/plugins/oxIpGeoBase/' . ($folders ? implode('/', $folders) . '/' : '') . $class . '.php';
		if (file_exists($filename)) {
			require_once $filename;
		}
	}
});

/**
 * PEAR-specific autoloader
 */
spl_autoload_register(function($class) {
	switch ($class) {
		case 'OX_PluginManager':
			$filename = MAX_PATH . '/lib/OX/Plugin/PluginManager.php';
			break;

		default:
			if (in_array(substr($class, 0, 3), array('OX_', 'OA_'))) {
				$filename = MAX_PATH . '/lib/';
			} else {
				$filename = MAX_PATH . '/lib/pear/';
			}
			$filename .= str_replace('_', '/', $class) . '.php';
	}

	if (file_exists($filename)) {
		require_once $filename;
	}
});

include_once MAX_PATH . '/www/admin/plugins/oxIpGeoBase/common.php';