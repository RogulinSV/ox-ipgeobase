<?php
/**
 * Filesystem
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 09.01.2015 12:47
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

defined('MAX_PATH') or die('Filesystem: required constants not defined');

class Filesystem
{
	/**
	 * Gets path for saving files
	 *
	 * @return string
	 */
	public static function getSafePath()
	{
		return MAX_PATH . '/var';
	} // function getSafePath

	/**
	 * Checks path for saving files
	 *
	 * @param string $path
	 * @return bool
	 */
	public static function isSafePath($path)
	{
		$path = realpath($path);
		$length = strlen(self::getSafePath());

		return ($length <= strlen($path) && substr($path, 0, $length) === self::getSafePath());
	} // function isSafePath
}