<?php
/**
 * Cache aware interface
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 24.12.2014 15:54
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

interface CacheAwareInterface
{
	/**
	 * Sets cache layer
	 *
	 * @param Cache $cache
	 * @return $this
	 */
	public function setCache(Cache $cache);
}