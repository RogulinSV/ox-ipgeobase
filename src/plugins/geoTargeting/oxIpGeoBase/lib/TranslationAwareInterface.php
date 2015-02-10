<?php
/**
 * TranslationAwareInterface.php
 *
 * @package none
 * @subpackage none
 * @since 19.01.2015 12:31
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

interface TranslationAwareInterface
{
	/**
	 * @param Translation $translation
	 * @return $this
	 */
	public function setTranslation(Translation $translation);
}