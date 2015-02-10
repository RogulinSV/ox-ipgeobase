<?php
/**
 * Form for manual data import
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 07.01.2015 17:19
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

use Plugins_GeoTargeting_Navigation_MenuBuilder as Menu;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\AbstractForm;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Factory;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Task;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Utils;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Filesystem;

defined('MAX_PATH') or die('Access denied');

class Plugins_GeoTargeting_Form_GeoImport extends AbstractForm
{
	/**
	 * @var \OX\plugins\geoTargeting\oxIpGeoBase\lib\Scheduling
	 */
	private $scheduler;

	/**
	 * @var bool
	 */
	private $disabled = false;

	/**
	 * @return \OX\plugins\geoTargeting\oxIpGeoBase\lib\Scheduling
	 */
	private function getScheduler()
	{
		if (is_null($this->scheduler)) {
			$config = Factory::config();
			$this->scheduler = Factory::scheduling($config);
		}

		return $this->scheduler;
	} // function getScheduler

	/**
	 * @return string
	 */
	public function getCode()
	{
		return 'ox_ipgeobase_geoimport';
	} // function getCode

	/**
	 * Configure form
	 *
	 * @return void
	 */
	public function configure()
	{
		$this->form->setMaxFileSize(3 * 1024 * 1024);

		$this->disabled = false;
		foreach (Task::supports() as $supports) {
			if (!$supports['support']) {
				$this->disabled = true;
				break;
			}
		}

		$this->form->addElement('header', null, $this->translate('form_import_header'));
		$this->form->addElement('static', 'Label', $this->translate('form_import_description_name'), $this->translate('form_import_description_hint'));
		/** @var HTML_QuickForm_file $file */
		$file = $this->form->addElement('file', 'name', $this->translate('form_import_field_upload_name'));
		$this->form->addRule('name', $this->translate('form_import_field_upload_hint'), 'required');
		/** @var HTML_QuickForm_input $email */
		$email = $this->form->addElement('text', 'email', $this->translate('form_import_field_email_name'));
		/** @var HTML_QuickForm_checkbox $checkbox */
		$checkbox = $this->form->addElement('checkbox', 'confirm', $this->translate('form_import_field_confirm_name'), $this->translate('form_import_field_confirm_hint'));
		$checkbox->setChecked(true);
		$this->form->addElement('controls', 'form-controls');
		/** @var HTML_QuickForm_submit $button */
		$submit = $this->form->addElement('submit', 'submit', $this->translate('form_button_submit_name'));

		if ($this->disabled) {
			$file->setAttribute('disabled', 'disabled');
			$email->setAttribute('disabled', 'disabled');
			$checkbox->setAttribute('disabled', 'disabled');
			$submit->setAttribute('disabled', 'disabled');
		}
	} // function configure

	/**
	 * Form processing
	 *
	 * @return bool
	 */
	public function process()
	{
		$error = '';
		$scheduler = $this->getScheduler();

		if (!$error && $this->disabled) {
			$error = $this->translate('form_import_error_requirements');
		}

		if (!$error && $scheduler->hasIncompleteTasks()) {
			if ($scheduler->hasRunningTasks()) {
				$error = $this->translate('form_import_error_still_running');
			} else if ($scheduler->hasAwaitingTasks()) {
				$error = $this->translate('form_import_error_still_awaiting');
			} else {
				$error = $this->translate('form_import_error_still_not_completed');
			}
		} // if

		if (!$error) {
			/** @var HTML_QuickForm_file $file */
			$file = $this->form->getElement('name');
			/** @var HTML_QuickForm_input $email */
			$email = $this->form->getElement('email');
			/** @var HTML_QuickForm_checkbox $confirm */
			$confirm = $this->form->getElement('confirm');

			if (self::error($file) or self::error($email) or self::error($confirm)) {
				$error = $this->translate('form_error_wrong_input_params');
			} else if (!$file->isUploadedFile()) {
				$error = $this->translate('form_import_error_not_uploaded');
			} else if ($email->getValue()) {
				$dll = new OA_Dll();
				if (!$dll->checkEmail($email->getValue())) {
					$error = $this->translate('form_import_error_invalid_email');
				}
			}
		} // if

		if (!$error) {
			$name = 'ipgeobase_' . \MAX_getRandomNumber(16);
			$path = Filesystem::getSafePath() . '/' . $name;
			if (!$file->moveUploadedFile(Filesystem::getSafePath(), $name)) {
				$error = $this->translate('form_import_error_not_uploaded');
			}
		}

		if (!$error) {
			$task = $scheduler->addTask($path, Factory::user());
			if ($task) {
				if ($confirm->getChecked()) {
					$options = array(
						'email' => ($email->getValue() ?: $task['author_email']),
						'user'  => $task['job_author'],
						'code'  => $task['job_code'],
						'session' => Utils::createSessionKey(
							array(
								$this->config['secret'],
								strtotime(date('Y-m-d H:i:00')),
								$task['job_code']
							)
						)
					);

					Utils::asyncRequest(
						Plugins_GeoTargeting_Admin_ConstructUrl('plugins/oxIpGeoBase/maintenance/maintenance.php'),
						$options
					);
				}
			} else {
				unlink($path);
				$error = $this->translate('form_import_error_not_scheduled');
			}
		} // if

		if (!$error) {
			$output = true;
			$this->flashMessage(
				$this->translate('form_import_message_success')
			);
		} else {
			$output = false;
			$this->flashError($error);
		}

		return $output;
	} // function process

	/**
	 * Display a form
	 *
	 * @return void
	 */
	public function display()
	{
		$this->restore();
		Menu::init($this->translation)->build('ipgeobase-import');

		$tpl = Plugins_GeoTargeting_Admin_Template('geo-import.tpl', 'ipgeobase-import');
		$tpl->assign('form', $this->form->serialize());
		$tpl->assign('list', $this->getTasksList());
		if ($this->disabled) {
			$errors = array();
			foreach (Task::supports() as $supports) {
				if (!$supports['support']) {
					$errors[] = $supports['message'];
				}
			}
			$tpl->assign('errors', $errors);
		}

		$hooks = new OX_Admin_UI_Hooks();
		$hooks->registerAfterPageHeaderListener(array($this, '_displayHeader'));

		phpAds_PageHeader('ipgeobase-import', '', '../../');
		$tpl->display();
		phpAds_PageFooter();
	} // function display

	/**
	 * Display a specific page header
	 *
	 * @return void
	 * @private
	 * @internal does not make sense, it`s does not work properly...
	 */
	public function _displayHeader()
	{
		$tpl = Plugins_GeoTargeting_Admin_Template('geo-import-blocks.tpl', 'ipgeobase-import-blocks');
		$tpl->assign('block', 'header');
		$tpl->display();
	} // function _displayHeader

	/**
	 * @return array
	 */
	private function getTasksList()
	{
		return $this->getScheduler()->getLatestTasks(10);
	} // function displayTasksList

	/**
	 * Recovery session token after re-submit the form
	 *
	 * @return void
	 * @internal security hole?
	 */
	private function restore()
	{
		/** @var HTML_QuickForm_hidden $token */
		$token = $this->form->getElement('token');
		if (!self::error($token)) {
			$token->setValue(phpAds_SessionGetToken());
		}
	} // function restore
}