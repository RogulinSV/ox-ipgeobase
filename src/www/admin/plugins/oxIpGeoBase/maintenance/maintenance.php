<?php
/**
 * Admin script for maintenance
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 19.12.2014 13:17
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 * @example php maintenance.php -v1 -m"mail@example.com"
 * @example php maintenance.php -v0
 * @example GET maintenance.php?email=<email>&code=<job_code>
 */

use OX\plugins\geoTargeting\oxIpGeoBase\lib\Utils;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Plugin;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Maintenance;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\MaintenanceNotifier as Notifier;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Factory;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Console;

$options = array();
$options['cli_mode']  = ('cli' === php_sapi_name());
$options['safe_mode'] = !!ini_get('safe_mode');
$options['verbose']   = false;

if (!$options['safe_mode']) {
	ignore_user_abort(true);
	set_time_limit(0);
}

// Require the timezone class, and get the system timezone, storing in a global variable
require_once dirname(__FILE__) . '/../../../../../lib/OX/Admin/Timezones.php';
$serverTimezone = OX_Admin_Timezones::getTimezone();

require_once dirname(__FILE__) . '/../../../../../init.php';
require_once MAX_PATH . '/lib/max/other/lib-io.inc.php';
require_once MAX_PATH . '/plugins/geoTargeting/oxIpGeoBase/oxIpGeoBase.autoload.php';

$code = null;
$user = null;
$email = null;
$session = null;
phpAds_registerGlobalUnslashed('email', 'user', 'code', 'session');
$separator = str_repeat('-', 40);

$config = Factory::config();
$logger = Factory::logger(Maintenance::LOGGER_CHANNEL);

if (!Plugin::isEnabled()) {
	$logger->notice(sprintf('--- plugin %s disabled, enable it or disable maintenance script ---', Plugin::COMPONENT_GEO_TARGETING_ID));
	exit;
}

$logger->debug('Started ' . $separator);
$logger->info(
	sprintf('maintenance started with params: cli mode %s, safe mode %s',
		($options['cli_mode'] ? 'enabled' : 'disabled'),
		($options['safe_mode'] ? 'enabled' : 'disabled')
	)
);
if ($options['cli_mode']) {
	if (function_exists('posix_geteuid')) {
		$user = posix_getpwuid(posix_geteuid());
		$user = $user['name'];
	} else {
		$user = @exec('whoami') ?: get_current_user();
	}

	$logger->info(
		sprintf('maintenance started by local user: %s from %s',
			$user,
			(!empty($_SERVER['SSH_CLIENT'])
				? $_SERVER['SSH_CLIENT']
				: (!empty($_SERVER['SSH_CONNECTION'])
					? $_SERVER['SSH_CONNECTION']
					: '-'
				)
			)
		)
	);
} else {
	$logger->info(
		sprintf('maintenance started by remote user: ip %s, host %s, via %s',
			$_SERVER['REMOTE_ADDR'],
			(!empty($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : '-'),
			(!empty($_SERVER['HTTP_VIA']) ? $_SERVER['HTTP_VIA'] : '-')
		)
	);

	for ($timestamp = strtotime(date('Y-m-d H:i:00')), $pass = false, $limit = $timestamp - 60; $timestamp >= $limit && !$pass; $timestamp -= 60) {
		$pass = Utils::checkSessionKey($session, array($config['secret'], $timestamp, $code));
	}
	if (!$pass) {
		$logger->critical(sprintf('failed to validate session: session %s', $session));
		exit;
	}
}

$maintenance = new Maintenance($config);
$maintenance->setLogger($logger);

if (!$options['cli_mode']) {
	$maintenance->processScheduledTaskByCode($code);
} else {
	$arguments = Utils::parseArguments(array('m', 'v'), array('email', 'verbose'));
	if (!empty($arguments['m'])) {
		$email = $arguments['m'];
	} else if (!empty($arguments['email'])) {
		$email = $arguments['email'];
	}
	if (isset($arguments['v'])) {
		$options['verbose'] = true;
	} else if (isset($arguments['verbose'])) {
		$options['verbose'] = true;
	}

	$maintenance->processLatestScheduledTask($user);
} // if

if ($email) {
	$notifier = new Notifier($maintenance, Factory::translation());
	if ($notifier->notify($email)) {
		$logger->info(sprintf('maintenance notification was send to %s', $email));
	} else {
		$logger->error(sprintf('maintenance notification was not send to %s', $email));
	}
}

if ($maintenance->hasErrors()) {
	$logger->error('maintenance stopped with result: errors occurred while processing task');
	if ($options['cli_mode'] && $options['verbose']) {
		Console::terminate('errors occurred while processing maintenance tasks');
	}
} else {
	$logger->info('maintenance stopped with result: task successfully processed');
	if ($options['cli_mode'] && $options['verbose']) {
		Console::success('maintenance task successfully processed');
	}
}

$logger->debug($separator . ' stopped');