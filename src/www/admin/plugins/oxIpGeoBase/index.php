<?php
/**
 * index.php
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 11.12.2014 12:17
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

require_once '../../../../init.php';
require_once '../../config.php';

// Redirect to the appropriate "Settings" page
if (OA_Permission::isAccount(OA_ACCOUNT_ADMIN)) {
	OX_Admin_Redirect::redirect('plugins/oxIpGeoBase/geo-import.php');
} else if (OA_Permission::isAccount(OA_ACCOUNT_MANAGER, OA_ACCOUNT_ADVERTISER)) {
	OX_Admin_Redirect::redirect('plugins/oxIpGeoBase/geo-cities.php');
} else {
	// Only the admin user can change "Settings", so send to
	// the "Preferences" page instead
	OX_Admin_Redirect::redirect('account-preferences-index.php');
}