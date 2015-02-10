<?php
/**
 * Data-specific extractor
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 24.12.2014 16:24
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

class Extractor
{
	/**
	 * @return Extractor
	 */
	public static function init()
	{
		return new self();
	} // function init

	/**
	 * Retrieves left boundary of IP-address
	 *
	 * @param string $value
	 * @return string
	 */
	public function extractIpLbound($value)
	{
		$chunks = explode('-', $value);

		return rtrim($chunks[0]);
	} // function extractIpLbound

	/**
	 * Retrieves right boundary of IP-address
	 *
	 * @param string $value
	 * @return string
	 */
	public function extractIpRbound($value)
	{
		$chunks = explode('-', $value);

		return ltrim($chunks[1]);
	} // function extractIpRbound

	/**
	 * Retrieves city ID
	 *
	 * @param string $value
	 * @return int|null
	 */
	public function extractCity($value)
	{
		$value = trim($value);

		return (is_numeric($value) ? (int)$value : null);
	} // function extractCity
}