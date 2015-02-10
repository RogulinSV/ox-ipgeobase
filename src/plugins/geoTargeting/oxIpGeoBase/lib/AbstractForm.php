<?php
/**
 * Abstract form
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 26.12.2014 17:04
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

require_once MAX_PATH . '/lib/OA/Admin/TemplatePlugin.php';
require_once MAX_PATH . '/lib/OA/Admin/UI/component/Form.php';
require_once MAX_PATH . '/lib/pear/HTML/QuickForm/file.php';
require_once MAX_PATH . '/lib/OX/Plugin/Component.php';
require_once MAX_PATH . '/lib/OX/Extension.php';

/**
 * @method void setDefaults() setDefaults(array $defaults)
 * @method bool validate() validate()
 */
abstract class AbstractForm implements TranslationAwareInterface
{
	const FORM_METHOD = 'POST';
	const FORM_ENCTYPE = 'multipart/form-data';

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * @var \OA_Admin_UI_Component_Form
	 */
	protected $form;

	/**
	 * @var Translation
	 */
	protected $translation;

	/**
	 * Default form constructor
	 *
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		$this->config = $config;
		$this->form = $this->build();
	} // function __construct

	/**
	 * Default static class generator
	 *
	 * @param array $config
	 * @param array $defaults
	 * @param Translation $translation
	 * @return static
	 */
	public static function init(array $config, array $defaults = null, Translation $translation = null)
	{
		$form = new static($config);
		if (!is_null($translation)) {
			$form->setTranslation($translation);
		}
		$form->configure();
		if (!is_null($defaults)) {
			$form->setDefaults($defaults);
		}

		return $form;
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
	 * @param array $replacements
	 * @return string
	 */
	protected function translate($string, array $replacements = array())
	{
		if (!is_null($this->translation)) {
			$string = $this->translation->translate($string, $replacements);
		}

		return $string;
	} // function translate

	/**
	 * @return \OA_Admin_UI_Component_Form
	 */
	protected function build()
	{
		$form = new \OA_Admin_UI_Component_Form($this->getCode(), static::FORM_METHOD, $_SERVER['SCRIPT_NAME'], null, array('enctype' => static::FORM_ENCTYPE));
		$form->forceClientValidation(true);

		return $form;
	} // function build

	/**
	 * @return bool
	 */
	abstract public function configure();

	/**
	 * @return bool
	 */
	abstract public function process();

	/**
	 * @return void
	 */
	abstract public function display();

	/**
	 * @return string
	 */
	abstract public function getCode();

	/**
	 * Checks if form element is instance of error
	 *
	 * @param mixed $element
	 * @return string
	 */
	protected static function error($element)
	{
		if ($element instanceof \HTML_QuickForm_Error) {
			return $element->getMessage();
		}

		return '';
	} // function error

	/**
	 * Saves message for showing in next session as flash-message
	 *
	 * @param string $message
	 * @param bool $local
	 */
	protected function flashMessage($message, $local = true)
	{
		$location = ($local ? 'local' : 'global');
		\OA_Admin_UI::queueMessage($message, $location, 'confirm', 0);
	} // function flashMessage

	/**
	 * Saves error for showing in next session as flash-message
	 *
	 * @param string $error
	 * @param bool $local
	 */
	protected function flashError($error, $local = true)
	{
		$location = ($local ? 'local' : 'global');
		\OA_Admin_UI::queueMessage($error, $location, 'error', 0);
	} // function flashError

	/**
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	final public function __call($method, array $arguments)
	{
		return call_user_func_array(array($this->form, $method), $arguments);
	} // function __call
}