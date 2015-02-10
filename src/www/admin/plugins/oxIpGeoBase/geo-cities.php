<?php
/**
 * Admin form for manual check ip-address
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 19.01.2015 13:45
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

use OX\plugins\geoTargeting\oxIpGeoBase\lib\Factory;

require_once '../../../../init.php';
require_once '../../config.php';
require_once './common.php';

OA_Permission::enforceAccount(OA_ACCOUNT_ADMIN, OA_ACCOUNT_MANAGER, OA_ACCOUNT_ADVERTISER);

$config = Factory::config();
$translation = Factory::translation();
$defaults = array();

$form = Plugins_GeoTargeting_Form_GeoCities::init($config, $defaults, $translation);
if ($form->validate()) {
	$form->process();
}
$form->display();