<?php
/**
 * Process the settings after saving config data
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 12.12.2014 18:03
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

use OX\plugins\geoTargeting\oxIpGeoBase\lib\Factory;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Utils;

class oxIpGeoBase_processSettings
{
	const PREFIX = 'oxIpGeoBase_';

	/**
	 * @param array $errors
	 * @return bool
	 * @see www/admin/plugin-settings.php
	 */
	public function validate(array &$errors)
	{
		$count = count($errors);

		$option = 'url';
		if (empty($GLOBALS[self::PREFIX . $option]) or !preg_match('/^https?:\/\//', $GLOBALS[self::PREFIX . $option])) {
			$errors[] = $this->translate('settings_error_invalid_url', array('%LABEL%' => $this->getLabel($option)));
		}

		foreach (array('dataIp', 'dataCities') as $option) {
			if (empty($GLOBALS[self::PREFIX . $option]) or '/' !== substr($GLOBALS[self::PREFIX . $option], 0, 1)) {
				$errors[] = $this->translate('settings_error_invalid_archive_path', array('%LABEL%' => $this->getLabel($option)));
			}
		}

		foreach (array('staleLimit', 'importBufferLimit', 'httpTimeout', 'httpRedirects') as $option) {
			if (empty($GLOBALS[self::PREFIX . $option]) or $GLOBALS[self::PREFIX . $option] < 0) {
				$errors[] = $this->translate('settings_error_invalid_number', array('%LABEL%' => $this->getLabel($option)));
			}
		}

		if (!empty($GLOBALS[self::PREFIX . 'httpProxy'])) {
			$options = array(
				'url' => $GLOBALS[self::PREFIX . 'url'],
				'httpProxy' => $GLOBALS[self::PREFIX . 'httpProxy'],
				'httpAuthName' => '',
				'httpAuthPass' => '',
				'httpUseragent' => '',
				'httpReferer' => '',
				'httpTimeout' => $GLOBALS[self::PREFIX . 'httpTimeout'],
				'httpRedirects' => $GLOBALS[self::PREFIX . 'httpRedirects']
			);
			if (!empty($GLOBALS[self::PREFIX . 'httpAuthName'])) {
				$options['httpAuthName'] = $GLOBALS[self::PREFIX . 'httpAuthName'];
			}
			if (!empty($GLOBALS[self::PREFIX . 'httpAuthPass'])) {
				$options['httpAuthPass'] = $GLOBALS[self::PREFIX . 'httpAuthPass'];
			}
			if (!empty($GLOBALS[self::PREFIX . 'httpUseragent'])) {
				$options['httpUseragent'] = $GLOBALS[self::PREFIX . 'httpUseragent'];
			}
			if (!empty($GLOBALS[self::PREFIX . 'httpReferer'])) {
				$options['httpReferer'] = $GLOBALS[self::PREFIX . 'httpReferer'];
			}

			if (!Utils::checkProxy($options)) {
				$errors[] = $this->translate('settings_error_invalid_proxy',
					array(
						'%PROXY%' => $this->getLabel('httpProxy'),
						'%AUTH_NAME%' => $this->getLabel('httpAuthName'),
						'%AUTH_PASS%' => $this->getLabel('httpAuthPass')
					)
				);
			} // if
		} // if

		return (count($errors) === $count);
	} // function validate

	/**
	 * @param string $name
	 * @return string
	 */
	private function getLabel($name)
	{
		$output = $name;

		$section = Factory::plugin('conf');
		foreach ($section['settings'] as $setting) {
			if ($setting['key'] === $name) {
				$output = $setting['label'];
				break;
			}
		}

		return $output;
	} // function getLabel

	/**
	 * @param string $message
	 * @param array $replacements
	 * @return string
	 */
	private function translate($message, array $replacements = array())
	{
		return Factory::translation()->translate($message, $replacements);
	} // function translate
}