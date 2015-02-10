<?php
/**
 * Services factory
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 24.12.2014 9:43
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

final class Factory
{
	/**
	 * The name of block that`s described in navigation parameters in installation file
	 * @see Plugins_GeoTargeting_Navigation_MenuBuilder::getMenu()
	 */
	const PLUGIN_NAVIGATION = 'navigation';

	/**
	 * @var array
	 */
	private static $instances = array();

	/**
	 * @var string
	 */
	private static $session;

	/**
	 * Gets session uid
	 *
	 * @return string
	 */
	private static function getSession()
	{
		if (is_null(self::$session)) {
			self::$session = uniqid();
		}

		return self::$session;
	} // function getSession

	/**
	 * Gets plugin installation data
	 *
	 * @param string $block
	 * @return array
	 * @todo rename method
	 */
	public static function plugin($block)
	{
		if (!isset(self::$instances['plugin'])) {
			$manager = new \OX_PluginManager();
			$installPath = $manager->getFilePathToXMLInstall(Plugin::COMPONENT_GEO_TARGETING_ID);
			$installData = $manager->parseXML($installPath, '\\OX_ParserComponentGroup');
			self::$instances['plugin'] = $installData;
		}

		$output = array();
		if (isset(self::$instances['plugin']['install'][$block])) {
			$output = self::$instances['plugin']['install'][$block];
		}

		return $output;
	} // function plugin

	/**
	 * Gets plugin settings
	 *
	 * @param array $config
	 * @return array
	 */
	public static function settings(array $config = null)
	{
		if (is_null($config)) {
			$manager = new \OX_PluginManager();
			$settings = $manager->getComponentGroupSettingsArray(Plugin::CONFIG_NAMESPACE);
		} else {
			if (isset($config[Plugin::CONFIG_NAMESPACE])) {
				$settings = $config[Plugin::CONFIG_NAMESPACE];
			} else {
				trigger_error('Failed to find settings of the plugin in configuration data', E_USER_ERROR);
			}
		}
		$settings['safePath'] = Filesystem::getSafePath();

		return $settings;
	} // function settings

	/**
	 * Gets system settings
	 *
	 * @param bool $fresh
	 * @return array
	 */
	public static function config($fresh = false)
	{
		if ($fresh) {
			$config = parseIniFile();
			if (!is_array($config) or !count($config)) {
				trigger_error('Failed to load raw configuration data', E_USER_ERROR);
			}
		} else if (isset($GLOBALS['_MAX']['CONF'])) {
			$config = $GLOBALS['_MAX']['CONF'];
		} else {
			trigger_error('The configuration data is not loaded', E_USER_ERROR);
		}

		return $config;
	} // function config

	/**
	 * Gets translation instance
	 *
	 * @return Translation
	 */
	public static function translation()
	{
		if (!isset(self::$instances['translation'])) {
			$translations = array();
			$defaultLocale = 'en';

			$locale = $defaultLocale;
			$filename = MAX_PATH . '/plugins/geoTargeting/oxIpGeoBase/lang/' . $locale . '.lang.php';
			if (file_exists($filename)) {
				$translations[$locale] = include $filename;
			}

			if (isset($GLOBALS['_MAX']['PREF']['language']) && $defaultLocale !== $GLOBALS['_MAX']['PREF']['language']) {
				$locale = $GLOBALS['_MAX']['PREF']['language'];

				$filename = MAX_PATH . '/plugins/geoTargeting/oxIpGeoBase/lang/' . $locale . '.lang.php';
				if (file_exists($filename)) {
					$translations[$locale] = include $filename;
				}
			} // if

			$translator = new \Zend_Translate('array', array());
			foreach ($translations as $locale => $translation) {
				if (!empty($translation)) {
					if ($locale !== $defaultLocale && !empty($translations[$defaultLocale])) {
						$translation = array_merge($translations[$defaultLocale], $translation);
					}
					/** @var \Zend_Translate_Adapter $translator */
					$translator->addTranslation($translation, $locale);
				}
			}

			/** @var \Zend_Translate $translator */
			$wrapper = new Translation($translator);
			self::$instances['translation'] = $wrapper;
		} // if

		return self::$instances['translation'];
	} // function translation

	/**
	 * Gets current user
	 *
	 * @return array [string 'email_address', string 'contact_name']
	 */
	public static function user()
	{
		$user = $GLOBALS['session']['user'];

		return ($user ? $user->aUser : array());
	} // function user

	/**
	 * Gets logger instance
	 *
	 * @param string $channel
	 * @return Logger
	 */
	public static function logger($channel)
	{
		if (!isset(self::$instances['logger'][$channel])) {
			$identifier = $channel . ':' . self::getSession();

			/** @var \Log_composite $logger */
			$logger = \Log::factory('composite');

			$config = self::config();
			if ($config['log']['enabled']) {
				$level = (int)$config['log']['priority'];
				$level = ($config['debug']['production'] && \Log::priorityToString($level))
					? $level
					: \PEAR_LOG_DEBUG;

				/** @var \Log_file $file */
				$file = \Log::factory('file', MAX_PATH . '/var/ipgeobase.log', $identifier, $level);
				$logger->addChild($file);
			} // if

			if ('cli' == php_sapi_name()) {
				$level = \PEAR_LOG_DEBUG;
				/** @var \Log_console $console */
				$console = \Log::factory('console', '', $identifier, $level);
				$logger->addChild($console);
			} // if

			self::$instances['logger'][$channel] = new Logger($logger);
		} // if

		return self::$instances['logger'][$channel];
	} // function logger

	/**
	 * Gets cache instance
	 *
	 * @param string $prefix
	 * @return Cache
	 */
	public static function cache($prefix)
	{
		if (!isset(self::$instances['cache'][$prefix])) {
			self::$instances['cache'][$prefix] = new Cache($prefix);
		}

		return self::$instances['cache'][$prefix];
	} // function cache

	/**
	 * Gets instance of targeting repository
	 *
	 * @param array $config
	 * @return Targeting
	 */
	public static function targeting(array $config)
	{
		$db = \OA_DB::singleton();
		$repository = new Targeting($config, $db);

		$cache = self::cache(Plugin::CACHE_PREFIX);
		$repository->setCache($cache);

		$logger = self::logger(Targeting::LOGGER_CHANNEL);
		$repository->setLogger($logger);

		return $repository;
	} // function targeting

	/**
	 * Gets instance of scheduler
	 *
	 * @param array $config
	 * @return Scheduling
	 */
	public static function scheduling(array $config)
	{
		$db = \OA_DB::singleton();
		$repository = new Scheduling($config, $db);

		$cache = self::cache(Plugin::CACHE_PREFIX);
		$repository->setCache($cache);

		$logger = self::logger(Scheduling::LOGGER_CHANNEL);
		$repository->setLogger($logger);

		return $repository;
	} // function scheduling

	/**
	 * Gets instance of task for maintenance
	 *
	 * @param array $config
	 * @return Task
	 */
	public static function task(array $config)
	{
		$task = new Task($config);

		$logger = self::logger(Task::LOGGER_CHANNEL);
		$task->setLogger($logger);

		return $task;
	} // function task
}