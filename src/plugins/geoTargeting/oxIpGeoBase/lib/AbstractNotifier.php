<?php
/**
 * Abstract informer
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 21.01.2015 16:27
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

abstract class AbstractNotifier
{
	/**
	 * @param mixed $recipient
	 * @return bool
	 */
	abstract public function notify($recipient);
}