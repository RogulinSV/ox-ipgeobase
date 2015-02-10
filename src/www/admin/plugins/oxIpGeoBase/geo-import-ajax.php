<?php
/**
 * geo-import-ajax.php
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 30.01.2015 17:10
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

use OX\plugins\geoTargeting\oxIpGeoBase\lib\Factory;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Scheduling;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\TaskProgress;

require_once '../../../../init.php';
require_once '../../config.php';
require_once './common.php';

if (!OA_Permission::isAccount(OA_ACCOUNT_ADMIN) or empty($_SERVER['HTTP_X_REQUESTED_WITH']) or 'XMLHttpRequest' !== $_SERVER['HTTP_X_REQUESTED_WITH']) {
	Plugins_GeoTargeting_Admin_AjaxBadRequest();
}

$action = null;
phpAds_registerGlobalUnslashed('action');

switch ($action) {
	case 'progress':
		$id = 0;
		phpAds_registerGlobalUnslashed('id');
		Plugins_GeoTargeting_Admin_AjaxOutput(
			ShowProgressAction((int)$id)
		);
		break;

	default:
		Plugins_GeoTargeting_Admin_AjaxBadRequest();
}

function ShowProgressAction($id)
{
	$output = null;

	$id = intval($id);
	if ($id > 0) {
		$config = Factory::config();
		$scheduling = Factory::scheduling($config);
		$task = $scheduling->getTaskById($id);
		if ($task) {
			$output = Utils_AjaxOutputMapping(array(
				'status'  => 'job_status',
				'created' => 'job_scheduled',
				'opened'  => 'job_started',
				'closed'  => 'job_completed'
			), $task);

			$output['percent'] = 0;
			if ($scheduling::isTaskOpened($task)) {
				$progress = new TaskProgress();
				$percent = $progress->read($id);
				$output['percent'] = $percent;
			}
		}
	}

	return $output;
} // function ShowProgressAction

/**
 * @param array $map
 * @param array $data
 * @return array
 */
function Utils_AjaxOutputMapping(array $map, array $data)
{
	$output = array();
	foreach ($map as $k => $v) {
		$output[$k] = (isset($data[$v]) ? $data[$v] : null);
	}

	return $output;
} // function Utils_AjaxOutputMapping