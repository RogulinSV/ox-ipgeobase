<?php
/**
 * Notifier about maintenance results
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 21.01.2015 16:42
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

class MaintenanceNotifier extends AbstractNotifier implements TranslationAwareInterface
{
	/**
	 * @var Translation
	 */
	private $translation;

	/**
	 * @var Maintenance
	 */
	private $maintenance;

	/**
	 * @param Maintenance $maintenance
	 * @param Translation $translation
	 */
	public function __construct(Maintenance $maintenance, Translation $translation)
	{
		$this->maintenance = $maintenance;
		$this->setTranslation($translation);
	} // function __construct

	/**
	 * @param Translation $translation
	 * @return $this
	 */
	public function setTranslation(Translation $translation)
	{
		$this->translation = $translation;

		return $this;
	} // function setTranslation

	/**
	 * Sends maintenance report by email
	 *
	 * @param string $email
	 * @return bool
	 */
	public function notify($email)
	{
		if ($this->maintenance->hasErrors()) {
			$subject = $this->translation->translate('maintenance_notification_subject_onfailure');
			$content = $this->translation->translate('maintenance_notification_content_onfailure', array(
				'%ERRORS%' => implode(PHP_EOL, $this->maintenance->getErrorsList())
			));
		} else {
			$subject = $this->translation->translate('maintenance_notification_subject_onsuccess');
			$content = $this->translation->translate('maintenance_notification_content_onsuccess');
		}

		$mailer = new \OA_Email();

		return $mailer->sendMail($subject, $content, $email);
	} // function notify
}