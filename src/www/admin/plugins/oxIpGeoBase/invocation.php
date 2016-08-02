<?php
/**
 * invocation.php
 *
 * @package none
 * @subpackage none
 * @since 23.12.2014 11:58
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

use OX\plugins\geoTargeting\oxIpGeoBase\lib\Plugin;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Factory;
use OX\plugins\geoTargeting\oxIpGeoBase\lib\Utils;

// The MAX_PATH below should point to the base of your Revive Adserver installation
define('MAX_PATH', '/opt/prod/trader');
define('OX_PATH', MAX_PATH);
ini_set('include_path', MAX_PATH . '/lib/pear/');
ini_set('display_errors', 0);
require_once MAX_PATH . '/lib/OA/DB.php';
require_once MAX_PATH . '/lib/pear/PEAR.php';
require_once MAX_PATH . '/lib/pear/Net/IPv4.php';

if (!empty($_GET['ip'])) {
    $net = new Net_IPv4();
    if ($net->check_ip($_GET['ip'])) {
        $_SERVER['REMOTE_ADDR'] = $_GET['ip'];
    }
}

if (@include_once(MAX_PATH . '/www/delivery/alocal.php')) {
    if (!isset($phpAds_context)) {
        $phpAds_context = array();
    }
    $phpAds_raw = view_local($what = '', $zoneid = 19, $campaignid = 132, $bannerid = 337, $target = '', $source = '', $withtext = '0', $phpAds_context, $charset = '');
}

$geo_data = Plugin::init(Factory::config())->delivery($_SERVER['REMOTE_ADDR']);
$geo_data = print_r($geo_data, true);
$geo_data = Utils::fromUtf($geo_data);

$html = <<<EOL
Remote address is {$_SERVER['REMOTE_ADDR']}<br>
<hr>
Banner invocation:<br>
{$phpAds_raw['html']}
<hr>
Geo location data:<br>
{$geo_data}
EOL;

print($html);