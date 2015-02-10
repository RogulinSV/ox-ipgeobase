<?php
/**
 * Translation.php
 *
 * @package none
 * @subpackage none
 * @since 15.01.2015 17:34
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

class Translation
{
	/**
	 * @var \Zend_Translate
	 */
	private $translation;

	/**
	 * @param \Zend_Translate $translation
	 */
	public function __construct(\Zend_Translate $translation)
	{
		$this->translation = $translation;
	} // function __construct

	/**
	 * @param string $string
	 * @param array $replacements
	 * @return mixed
	 */
	public function translate($string, array $replacements = array())
	{
		if ($this->translation->isTranslated($string)) {
			$string = $this->translation->translate($string);
		} else {
			trigger_error(
				sprintf('translation: key %s has no translation for locale %s', $string, $this->translation->getLocale()),
				E_USER_NOTICE
			);
		}

		if ($replacements) {
			$string = str_replace(
				array_keys($replacements),
				array_values($replacements),
				$string
			);
		}

		return $string;
	} // function translate

	/**
	 * @param string $string
	 * @return bool
	 */
	public function hasTranslation($string)
	{
		return $this->translation->isTranslated($string);
	} // function hasTranslation
}