<?php
/**
 * Admin form for manual data import
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 11.12.2014 12:23
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

use OX\plugins\geoTargeting\oxIpGeoBase\lib\Factory;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\SweepCommand;

require_once '../../../../init.php';
require_once '../../config.php';
require_once './common.php';

OA_Permission::enforceAccount(OA_ACCOUNT_ADMIN);

$action = null;
phpAds_registerGlobalUnslashed('action');

switch ($action) {
	case 'cancel':
		$id = 0;
		phpAds_registerGlobalUnslashed('id');
		CancelAction((int)$id);
		break;

	default:
		$email = null;
		phpAds_registerGlobalUnslashed('email');
		DefaultAction($email);
		break;
}

/**
 * Display or process form
 *
 * @param string $email
 */
function DefaultAction($email)
{
	$user = Factory::user();
	$config = Factory::config();
	$translation = Factory::translation();

	$defaults = array(
		'email' => ($email ? $email : $user['email_address'])
	);

	$form = Plugins_GeoTargeting_Form_GeoImport::init($config, $defaults, $translation);
	if ($form->validate() && $form->process()) {
		Plugins_GeoTargeting_Admin_Redirect('plugins/oxIpGeoBase/geo-import.php');
	}
	$form->display();
} // function DefaultAction

/**
 * Cancel task
 *
 * @param int $id
 */
function CancelAction($id)
{
	if ($id > 0) {
		$config = Factory::config();
		$scheduler = Factory::scheduling($config);
		$translation = Factory::translation();

		$task = $scheduler->getTaskById($id);
		if ($task && $scheduler::isTaskAwaiting($task)) {
			if ($scheduler->closeTask($id, $scheduler::STATUS_FAIL)) {
				/** @var \MDB2_Driver_Manager_Common $manager */
				// $manager = \OA_DB::singleton()->manager;
				$logger = Factory::logger('sweeper');
				if (SweepCommand::init($task)->setLogger($logger)->run()) {
					$message = $translation->translate('action_cancel_task_onsuccess', array('%ID%' => $id));
					\OA_Admin_UI::queueMessage($message, 'global', 'confirm', 0);
				} else {
					$message = $translation->translate('action_cancel_task_onsuccess', array('%ID%' => $id));
					\OA_Admin_UI::queueMessage($message, 'global', 'warning', 0);
				}
			} else {
				$message = $translation->translate('action_cancel_task_onfailure', array('%ID%' => $id));
				\OA_Admin_UI::queueMessage($message, 'global', 'error', 0);
			}
		} else {
			$message = $translation->translate('action_cancel_task_onfailure', array('%ID%' => $id));
			\OA_Admin_UI::queueMessage($message, 'global', 'error', 0);
		}
	} else {
		// do nothing...
	}

	Plugins_GeoTargeting_Admin_Redirect('plugins/oxIpGeoBase/geo-import.php');
} // function CancelAction