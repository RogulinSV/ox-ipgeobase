<?php
/**
 * Utils.php
 *
 * @package none
 * @subpackage none
 * @since 23.12.2014 16:04
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

class Utils
{
	/**
	 * @param array $data
	 * @param int $depth
	 * @return string
	 */
	public static function packData(array $data, $depth = 1)
	{
		return json_encode($data, 0, $depth);
	} // function packData

	/**
	 * @param string $data
	 * @param int $depth
	 * @return array
	 */
	public static function unpackData($data, $depth = 1)
	{
		return json_decode((string)$data, true, $depth);
	} // function unpackData

	/**
	 * @param array $arguments
	 * @return string
	 */
	public static function createSessionKey(array $arguments)
	{
		$arguments = array_map(function($argument) {
			return strval($argument);
		}, $arguments);
		sort($arguments, SORT_NATURAL);
		$key = '0xDEADBEEF' . MAX_PATH . implode(':', $arguments);

		return md5($key);
	} // function createSessionKey

	/**
	 * @param string $key
	 * @param array $arguments
	 * @return bool
	 */
	public static function checkSessionKey($key, array $arguments)
	{
		return (self::createSessionKey($arguments) === $key);
	} // function checkSessionKey

	/**
	 * @param string $string
	 * @return string
	 */
	public static function toUtf($string)
	{
		return \MAX_commonConvertEncoding($string, 'UTF-8', 'CP-1251');
	} // function toUtf

	/**
	 * @param string $string
	 * @return string
	 */
	public static function fromUtf($string)
	{
		return \MAX_commonConvertEncoding($string, 'CP-1251', 'UTF-8');
	} // function fromUtf

	/**
	 * @param string $string
	 * @return string
	 */
	public static function underscoreCamelCased($string)
	{
		$string = preg_replace('/([A-Z]+)([A-Z][a-z])/', '\\1_\\2', $string);
		$string = preg_replace('/([a-z\d])([A-Z])/', '\\1_\\2', $string);
		$string = str_replace('-', '_', strtolower($string));

		return $string;
	} // function underscoreCamelCased

	/**
	 * @param int $bytes
	 * @param int $dec
	 * @return string
	 * @see http://stackoverflow.com/questions/15188033/human-readable-file-size
	 */
	public static function purifyBytes($bytes, $dec = 2)
	{
		$size   = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		$factor = floor((strlen($bytes) - 1) / 3);

		return sprintf("%.{$dec}f", $bytes / pow(1024, $factor)) . @$size[$factor];
	} // function purifyBytes

	/**
	 * @param string $uri
	 * @param array $params
	 */
	public static function asyncRequest($uri, array $params = array())
	{
		foreach ($params as $key => $value) {
			if (is_array($value)) {
				$value = implode(',', $value);
			}
			$params[$key] = $key . '=' . urlencode($value);
		}
		$post = implode('&', $params);

		$parts = parse_url($uri);

		$fp = fsockopen($parts['host'], (isset($parts['port']) ? $parts['port'] : 80), $errno, $error, 1);
		stream_set_timeout($fp, 0, 250);

		$out  = "POST {$parts['path']} HTTP/1.1\r\n";
		$out .= "Host: {$parts['host']}\r\n";
		$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$out .= "Content-Length: ".strlen($post)."\r\n";
		$out .= "Connection: Close\r\n\r\n";
		$out .= $post;

		fwrite($fp, $out);
		usleep(100000);
		fclose($fp);
	} // function asyncRequest

	/**
	 * Checks the http-connection via proxy
	 *
	 * @param array $options [string 'url', int 'httpTimeout', int 'httpRedirects', string 'httpProxy', string 'httpAuthName', string 'httpAuthPass', string 'httpUseragent', string 'httpReferer']
	 * @return bool
	 */
	public static function checkProxy(array $options)
	{
		$output = false;
		if (false !== ($ch = curl_init())) {
			curl_setopt($ch, CURLOPT_URL, $options['url']);
			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, $options['httpTimeout'] ?: 10);
			curl_setopt($ch, CURLOPT_MAXREDIRS, $options['httpRedirects'] ?: 0);
			curl_setopt($ch, CURLOPT_PROXY, $options['httpProxy']);
			if ($options['httpAuthName'] or $options['httpAuthPass']) {
				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $options['httpAuthName'] . ':' . $options['httpAuthPass']);
			}
			if ($options['httpUseragent']) {
				curl_setopt($ch, CURLOPT_USERAGENT, $options['httpUseragent']);
			}
			if ($options['httpReferer']) {
				curl_setopt($ch, CURLOPT_REFERER, $options['httpReferer']);
			}

			curl_exec($ch); // will return headers only
			$error = curl_errno($ch);
			$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			$output = (!$error && 200 == $code);
		} // if

		return $output;
	} // function checkProxy

	/**
	 * @param array $short
	 * @param array $long
	 * @return array
	 */
	public static function parseArguments(array $short, array $long = array())
	{
		$output = array_merge(
			array_fill_keys($short, null),
			array_fill_keys($long, null)
		);

		$pear = new \PEAR();
		$console = new \Console_Getopt();
		$arguments = $console->readPHPArgv();
		if (!$pear->isError($arguments)) {
			foreach ($arguments as $argument) {
				if ('-' === substr($argument, 0, 1)) {
					break;
				}
				array_shift($arguments);
			}
			foreach ($short as $k => $option) {
				$short[$k] = $option . ':';
			}
			foreach ($long as $k => $option) {
				$long[$k] = $option . '=';
			}
			$options = $console->getopt2($arguments, implode('', $short), $long);
			if (!$pear->isError($options)) {
				$arguments = array();
				foreach ($options[0] as $option) {
					$arguments[ltrim($option[0], '-')] = $option[1];
				}
				$output = array_merge($output, $arguments);
			}
		}

		return $output;
	} // function parseArguments
}