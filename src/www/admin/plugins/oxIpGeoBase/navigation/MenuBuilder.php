<?php
/**
 * Admin menu builder
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 10.12.2014 15:13
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

use OX\plugins\geoTargeting\oxIpGeoBase\lib\Factory;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Translation;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\TranslationAwareInterface;

defined('MAX_PATH') or die('Access denied');

class Plugins_GeoTargeting_Navigation_MenuBuilder implements TranslationAwareInterface
{
	const SECTION_IMPORT  = 'ipgeobase-import';
	const SECTION_SERVICE = 'ipgeobase-service';

	/**
	 * @var OA_Admin_Menu
	 */
	private $menu;

	/**
	 * @var \OX\plugins\geoTargeting\oxIpGeoBase\lib\Translation
	 */
	private $translation;

	/**
	 * @param Translation $translation
	 * @return Plugins_GeoTargeting_Navigation_MenuBuilder
	 */
	public static function init(Translation $translation = null)
	{
		$instance = new self();
		if (!is_null($translation)) {
			$instance->setTranslation($translation);
		}

		return $instance;
	} // function init

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
	 * @param string $string
	 * @return string
	 */
	private function translate($string)
	{
		if (!is_null($this->translation)) {
			$string = $this->translation->translate(
				$this->normalize($string)
			);
		}

		return $string;
	} // function translate

	/**
	 * @param string $string
	 * @return string
	 */
	private function normalize($string)
	{
		return trim(str_replace(' ', '_', strtolower($string)));
	} // function normalize

	/**
	 * Gets menu element by code
	 *
	 * @param string $id
	 * @return null|OA_Admin_Menu_Section
	 */
	public function get($id)
	{
		return $this->getMenu()->get($id);
	} // function get

	/**
	 * Builds entire menu, sets active element by code
	 *
	 * @param string $id
	 */
	public function build($id)
	{
		$section = $this->getMenu()->get($id);
		if ($section) {
			$section = $section->getParent();
			if ($section) {
				foreach ($section->getSections() as $section) {
					addLeftMenuSubItem($section->getId(), $this->translate($section->getName()), $section->getLink());
					if ($section->getId() === $id) {
						setCurrentLeftMenuSubItem($section->getId());
					}
				}
			}
		}
	} // function build

	/**
	 * @return OA_Admin_Menu
	 */
	private function getMenu()
	{
		if (is_null($this->menu)) {
			$cache = array();
			foreach (Factory::plugin(Factory::PLUGIN_NAVIGATION) as $account => $sections) {
				if (!OA_Permission::getAccountTable($account)) {
					continue;
				}
				foreach ($sections as $section) {
					if (!isset($cache[$section['index']])) {
						$cache[$section['index']] = $section;
						$cache[$section['index']]['accounts'] = array();
					}
					$cache[$section['index']]['accounts'][] = $account;
				}
			} // foreach

			$menu = new OA_Admin_Menu;
			foreach ($cache as $section) {
				$parent = $menu->get($section['addto']);
				$section = new OA_Admin_Menu_Section($section['index'], $section['value'], $section['link'], false, null, $section['accounts']);
				$checker = new Plugins_GeoTargeting_Navigation_MenuChecker();
				$section->setChecker($checker);
				if ($parent) {
					$section->setParent($parent);
					$menu->addTo($section->getParent()->getId(), $section);
				} else {
					$menu->add($section);
				}
				unset($section, $checker, $parent); // because everything by reference (OA_Admin_Menu::add(), OA_Admin_Menu_Section::setParent(), etc)!!!
			} // foreach

			$this->menu = $menu;
		} // if

		return $this->menu;
	} // function getMenu
}