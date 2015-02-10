<?php
/**
 * Logger aware interface
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 24.12.2014 15:54
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

interface LoggerAwareInterface
{
	/**
	 * Sets logging layer
	 *
	 * @param Logger $logger
	 * @return $this
	 */
	public function setLogger(Logger $logger);
}