<?php
/**
 * Form for manual check ip-address
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 19.01.2015 13:53
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

use Plugins_GeoTargeting_Navigation_MenuBuilder as Menu;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\AbstractForm;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Factory;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Targeting;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Utils;

defined('MAX_PATH') or die('Access denied');

class Plugins_GeoTargeting_Form_GeoCities extends AbstractForm
{
	/**
	 * @var \OX\plugins\geoTargeting\oxIpGeoBase\lib\Targeting
	 */
	private $targeting;

	/**
	 * @return Targeting
	 */
	private function getTargeting()
	{
		if (is_null($this->targeting)) {
			$config = Factory::config();
			$this->targeting = Factory::targeting($config);
		}

		return $this->targeting;
	} // function getTargeting

	/**
	 * @return string
	 */
	public function getCode()
	{
		return 'ox_ipgeobase_geocities';
	} // function getCode

	/**
	 * Configure form
	 *
	 * @return void
	 */
	public function configure()
	{
		$this->form->addElement('header', null, $this->translate('form_cities_header'));
		$this->form->addElement('static', 'Label', $this->translate('form_cities_description_name'), $this->translate('form_cities_description_hint'));
		$this->form->addElement('text', 'ip', $this->translate('form_cities_field_ip_name'));
		/** @var HTML_QuickForm_hierselect $select */
		$select = $this->form->addElement('hierselect', 'cities', $this->translate('form_cities_field_select_name'));
		$select->setOptions($this->getCitiesList());
		//$this->form->registerRule('ipv4', 'regex', 'aaa');
		//$this->form->addRule('ip', $this->translate('form_cities_field_ip_hint'), 'ipv4');
		$this->form->addElement('static', 'bounds', $this->translate('form_cities_field_bounds_name'), '');
		$this->form->addElement('controls', 'form-controls');
		$this->form->addElement('submit', 'submit', $this->translate('form_button_submit_name'));
	} // function configure

	/**
	 * @return array
	 */
	private function getCitiesList()
	{
		$output = array(
			0 => array(0 => $this->translate('form_cities_field_select_default')),
			1 => array()
		);
		foreach ($this->getTargeting()->getCitiesList() as $countryCode => $citiesList) {
			$output[0][$countryCode] = $countryCode;
			$output[1][$countryCode][0] = $this->translate('form_cities_field_select_default');
			foreach ($citiesList as $cityId => $cityName) {
				$output[1][$countryCode][$cityId] = Utils::toUtf($cityName);
			}
		}

		return array($output[0], $output[1]);
	} // function getCitiesList

	/**
	 * Form processing
	 *
	 * @return bool
	 */
	public function process()
	{
		$error = '';

		if (!$error) {
			/** @var HTML_QuickForm_hierselect $select */
			$select = $this->form->getElement('cities');
			/** @var HTML_QuickForm_input $ip */
			$ip = $this->form->getElement('ip');
			/** @var HTML_QuickForm_static $bounds */
			$bounds = $this->form->getElement('bounds');
			if (self::error($select) or self::error($ip) or self::error($bounds)) {
				$error = $this->translate('form_error_wrong_input_params');
			} else {
				$net = new Net_IPv4();
				if (!$ip->getValue() or !$net->validateIP($ip->getValue())) {
					$error = $this->translate('form_error_wrong_ip_address');
				}
			}
		}

		if (!$error) {
			$data = $select->getValue();
			if (!empty($data[0][0])) {
				$countryCode = $data[0][0];
			}
			if (!empty($data[1][0])) {
				$cityId = $data[1][0];
			}
			$geoData = $this->getTargeting()->findRegionData(
				$ip->getValue()
			);
			if (!$geoData) {
				$error = $this->translate('form_error_ip_not_found', array('%IP%' => $ip->getValue()));
			} else {
				if (!empty($cityId) && (int)$geoData['city_id'] !== (int)$cityId) {
					$error = $this->translate('form_error_wrong_city');
				} else if (!empty($countryCode) && $geoData['country_code'] !== $countryCode) {
					$error = $this->translate('form_error_wrong_country');
				}
			}
		} // if

		if (!$error) {
			$output = true;
			$this->flashMessage(
				$this->translate('form_cities_message_success')
			);

			$select->setValue(
				array($geoData['country_code'], $geoData['city_id'])
			);
			$bounds->setValue(
				sprintf('%s — %s (country: %s, host: %s)', $geoData['ip4_lbound'], $geoData['ip4_rbound'], $geoData['country_code'], gethostbyaddr($ip->getValue()))
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
		Menu::init($this->translation)->build('ipgeobase-cities');

		$tpl = Plugins_GeoTargeting_Admin_Template('geo-cities.tpl', 'ipgeobase-cities');
		$tpl->assign('form', $this->form->serialize());

		phpAds_PageHeader('ipgeobase-cities', '', '../../');
		$tpl->display();
		phpAds_PageFooter();
	} // function display

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