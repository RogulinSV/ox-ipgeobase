<?php
/**
 * Admin function library
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 28.12.2014 18:49
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

use OX\plugins\geoTargeting\oxIpGeoBase\lib\Factory;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Utils;

defined('MAX_PATH') or die('Access denied');

/**
 * Smarty blocks form handler
 *
 * @param string $template
 * @param array $params
 * @param string $content
 * @param Smarty $smarty
 * @param bool $repeat
 * @return string
 * @see OA_Admin_Template::_block_form_element()
 */
function Plugins_GeoTargeting_Admin_Template_BlockFormElementProcessor($template, array $params, $content, Smarty $smarty, &$repeat)
{
	static $break = false;

	if ($repeat && $params['elem']['type'] == 'header') {
		$break = false; //do not display breaks for first element in section
	}
	if (!$repeat) {
		$params['content'] = $content;
		if (isset($params['elem']) && is_array($params)) {
			$params += $params['elem'];
		}
		if (!isset($params['break'])) {
			$params['break'] = $break;
		}

		//if macro invoked with parent parameter do not add break
		if (isset($params['parent'])) {
			$params['break'] = false;
		}

		//put some context for form elements (set parent)
		if (is_array($smarty->_tag_stack) && count($smarty->_tag_stack) > 0) {
			if (isset($smarty->_tag_stack[0][1]['elem']['type'])) {
				$params['parent_tag'] = $smarty->_tag_stack[0][1]['elem']['type'];
			}
		}

		//store old _e if recursion happens
		$old_e = $smarty->get_template_vars('_e');
		$smarty->assign('_e', $params);
		$result = $smarty->fetch(MAX_PATH . $template);
		$smarty->clear_assign('_e');

		//restore old _e (if any)
		if (isset($old_e)) {
			$smarty->assign('_e', $old_e);
		}

		//decorate result with decorators content
		if (!empty($params['decorators']['list'])) {
			foreach ($params['decorators']['list'] as $decorator) {
				$result = $decorator->render($result);
			}
		}

		$break = ($params['type'] != 'header');

		return $result;
	}
} // function Plugins_GeoTargeting_Admin_Template_BlockFormElementProcessor

/**
 * Smarty blocks form handler factory
 *
 * @param array $params
 * @param string $content
 * @param Smarty $smarty
 * @param bool $repeat
 * @return string
 * @see Plugins_GeoTargeting_Admin_Template_BlockFormElementProcessor()
 */
function Plugins_GeoTargeting_Admin_Template_BlockFormElementProcessor_Preset(array $params, $content, Smarty $smarty, &$repeat)
{
	$template = '/www/admin/plugins/oxIpGeoBase/templates/form/elements.tpl';

	return Plugins_GeoTargeting_Admin_Template_BlockFormElementProcessor($template, $params, $content, $smarty, $repeat);
} // function Plugins_GeoTargeting_Admin_Template_BlockFormElementProcessor_Preset

/**
 * Smarty translation handler
 *
 * @param array $params
 * @param Smarty $smarty
 * @return string
 */
function Plugins_GeoTargeting_Admin_Template_Translation(array $params, Smarty $smarty)
{
	$output = '';
	if (!empty($params['str'])) {
		$translation = Factory::translation();
		$output = Utils::underscoreCamelCased($params['str']);
		if ($translation->hasTranslation($output)) {
			$output = $translation->translate($output);
		} else if (is_callable(array($smarty, '_function_t'))) {
			$output = call_user_func_array(array($smarty, '_function_t'), array($params, &$smarty));
		}
	} else {
		$smarty->trigger_error('t: missing \'str\' parameter: ' . $params['str']);
	}

	return $output;
} // function Plugins_GeoTargeting_Admin_Template_Translation

/**
 * Admin templates factory
 *
 * @param string $template
 * @param string $name
 * @return OA_Plugin_Template
 */
function Plugins_GeoTargeting_Admin_Template($template, $name)
{
	$tpl = new OA_Plugin_Template($template, $name);

	$tpl->unregister_block('oa_form_element');
	$tpl->register_block('oa_form_element', 'Plugins_GeoTargeting_Admin_Template_BlockFormElementProcessor_Preset');
	$tpl->unregister_function('t');
	$tpl->register_function('t', 'Plugins_GeoTargeting_Admin_Template_Translation');

	return $tpl;
} // function Plugins_GeoTargeting_Admin_Template_Extend

/**
 * Admin url builder
 *
 * @param string $path
 * @return null|string
 */
function Plugins_GeoTargeting_Admin_ConstructUrl($path)
{
	return MAX::constructURL(MAX_URL_ADMIN, $path);
} // function Plugins_GeoTargeting_Admin_ConstructUrl

/**
 * Admin http-redirect
 *
 * @param string $path
 */
function Plugins_GeoTargeting_Admin_Redirect($path)
{
	$redirect = new OX_Admin_Redirect();
	$redirect->redirect($path);
} // function Plugins_GeoTargeting_Admin_Redirect

/**
 * @param array $output
 */
function Plugins_GeoTargeting_Admin_AjaxOutput(array $output)
{
	print json_encode($output);
	exit;
} // function Plugins_GeoTargeting_Admin_AjaxOutput

/**
 * @return void
 */
function Plugins_GeoTargeting_Admin_AjaxBadRequest()
{
	header('HTTP/1.1 400 Bad Request');
	exit;
} // function Plugins_GeoTargeting_Admin_AjaxBadRequest