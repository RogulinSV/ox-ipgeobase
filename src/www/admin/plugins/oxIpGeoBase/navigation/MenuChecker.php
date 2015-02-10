<?php
/**
 * Plugin menu access permissions checker
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 28.12.2014 17:28
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

require_once MAX_PATH . '/lib/OA/Admin/Menu/IChecker.php';
require_once MAX_PATH . '/lib/OA/Admin/Menu/SectionAccountChecker.php';

defined('MAX_PATH') or die('Access denied');

class Plugins_GeoTargeting_Navigation_MenuChecker extends OA_Admin_SectionAccountChecker implements OA_Admin_Menu_IChecker
{
	/**
	 * Checks access permissions for menu section
	 *
	 * @param OA_Admin_Menu_Section $section
	 * @return bool
	 */
	public function check($section)
	{
		return parent::check($section);
	} // function check
}