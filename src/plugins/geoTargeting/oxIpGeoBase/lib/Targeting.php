<?php
/**
 * DB repository for targeting
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 24.12.2014 10:05
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

class Targeting extends AbstractRepository
{
	/**
	 * Version of repository
	 */
	const VERSION = 2;

	/**
	 * Channel for logger
	 */
	const LOGGER_CHANNEL = 'targeting';

	/**
	 * @return array
	 */
	public static function defaultTables()
	{
		return array('ipgeobase', 'ipgeobase_city');
	} // function defaultTables

	/**
	 * @return int
	 */
	public function getVersion()
	{
		return self::VERSION;
	} // function getVersion

	/**
	 * Finds geo data about region by specific IP-address
	 *
	 * @param string $ip
	 * @return bool|array
	 */
	public function findRegionData($ip)
	{
		$ip = ip2long($ip);
		if (false === $ip) {
			return false;
		} // if

		if ($ip < 0) {
			$ip += pow(2, 32);
		}

		$caching = (bool)$this->config[Plugin::CONFIG_NAMESPACE]['cacheCommon'];
		$key = $this->key('ipgeobase/ip', $ip);
		if ($caching && $this->cache && false !== ($output = $this->cache->get($key))) {
			return $output;
		}

		$table = $this->db->quoteIdentifier(
			$this->config['table']['prefix'] . $this->config[Plugin::CONFIG_NAMESPACE]['dbIpTable']
		);

		/*$query = '
			SELECT
				ip4_lbound,
				ip4_rbound,
				country_code,
				city_id
			FROM
				' . $table . '
			WHERE
				' . $ip . ' BETWEEN ip_lbound AND ip_rbound
			ORDER BY
				ip_rbound - ip_lbound ASC
			LIMIT
				1
		';*/
		$query = '
			SELECT
				*
			FROM
				' . $table . '
			WHERE
				ip_rbound >= ' . $ip . '
			ORDER BY
				ip_rbound ASC
			LIMIT 1';

		$output = $this->db->getRow($query);
		if (!!($error = self::error($output))) {
			$this->logError($error);
		}
		if ($output && $output['ip_lbound'] > $ip) {
			$output = null;
		}
		if ($caching && $this->cache) {
			$this->cache->set($key, $output, $this->expiredMonth());
		}

		return $output;
	} // function findRegionData

	/**
	 * Gets city's data
	 *
	 * @param int $cityId
	 * @return bool|array
	 */
	public function getCityData($cityId)
	{
		$cityId = (int)$cityId;

		$caching = (bool)$this->config[Plugin::CONFIG_NAMESPACE]['cacheCommon'];
		$key = $this->key('ipgeobase/city', $cityId);
		if ($caching && $this->cache && false !== ($output = $this->cache->get($key))) {
			return $output;
		}

		$table = $this->db->quoteIdentifier(
			$this->config['table']['prefix'] . $this->config[Plugin::CONFIG_NAMESPACE]['dbCitiesTable']
		);

		$query = '
			SELECT
				city_id,
				city_name,
				region_name,
				latitude,
				longitude
			FROM
				' . $table . '
			WHERE
				city_id = ' . $cityId . '
		';

		$output = $this->db->getRow($query);
		if (!($error = self::error($output))) {
			$output['region_code'] = self::getRegionCodeByName($output['region_name']);
		} else {
			$this->logError($error);
		}
		if ($caching && $this->cache) {
			$this->cache->set($key, $output, $this->expiredMonth());
		}

		return $output;
	} // function getCityData

	/**
	 * Gets regions list, optionally filtered by countries list
	 *
	 * @param array $countries
	 * @return array
	 */
	public function getRegionsList(array $countries = null)
	{
		$output = array();

		$caching = (
			   (bool)$this->config[Plugin::CONFIG_NAMESPACE]['cacheCommon']
			&& (bool)$this->config[Plugin::CONFIG_NAMESPACE]['cacheExtended']
		);
		if (!is_null($countries)) {
			sort($countries, SORT_NATURAL);
			$key = $this->key('ipgeobase/regions', md5(serialize($countries)));
		} else {
			$key = $this->key('ipgeobase/regions');
		}
		if ($caching && $this->cache && false !== ($output = $this->cache->get($key))) {
			return $output;
		}

		$cTable = $this->db->quoteIdentifier(
			$this->config['table']['prefix'] . $this->config[Plugin::CONFIG_NAMESPACE]['dbIpTable']
		);
		$rTable = $this->db->quoteIdentifier(
			$this->config['table']['prefix'] . $this->config[Plugin::CONFIG_NAMESPACE]['dbCitiesTable']
		);

		$query = '
			SELECT
				' . $cTable . '.country_code,
				' . $rTable . '.region_name
			FROM
				' . $cTable . '
			INNER JOIN
				' . $rTable . '
			ON
				' . $cTable . '.city_id = ' . $rTable . '.city_id
			WHERE
				' . $cTable . '.city_id IS NOT NULL
		';
		if (!is_null($countries)) {
			foreach ($countries as &$country) {
				$country = $this->escape($country);
			}
			$query .= ' AND ' . $cTable . '.country_code IN (' . implode(', ', $countries) . ')';
		}
		$query .= '
			ORDER BY
				' . $cTable . '.country_code ASC,
				' . $rTable . '.region_name ASC
		';

		$output = array();
		$stmt = $this->db->query($query);
		if (!($error = self::error($stmt))) {
			while ($data = $stmt->fetchRow()) {
				$data['region_code'] = self::getRegionCodeByName($data['region_name']);
				$output[$data['country_code']][$data['region_code']] = $data['region_name'];
			}

			if ($caching && $this->cache && $output) {
				$this->cache->set($key, $output, $this->expiredMonth());
			}
		} else {
			$this->logError($error);
		}

		return $output;
	} // function getRegionsList

	/**
	 * Gets cities list
	 *
	 * @return array
	 */
	public function getCitiesList()
	{
		$caching = (
			   (bool)$this->config[Plugin::CONFIG_NAMESPACE]['cacheCommon']
			&& (bool)$this->config[Plugin::CONFIG_NAMESPACE]['cacheExtended']
		);
		$key = $this->key('ipgeobase/cities');
		if ($caching && $this->cache && false !== ($output = $this->cache->get($key))) {
			return $output;
		}

		$cTable = $this->db->quoteIdentifier(
			$this->config['table']['prefix'] . $this->config[Plugin::CONFIG_NAMESPACE]['dbIpTable']
		);
		$rTable = $this->db->quoteIdentifier(
			$this->config['table']['prefix'] . $this->config[Plugin::CONFIG_NAMESPACE]['dbCitiesTable']
		);

		$query = '
			SELECT DISTINCT
				' . $cTable . '.country_code,
				' . $cTable . '.city_id,
				' . $rTable . '.city_name
			FROM
				' . $cTable . '
			INNER JOIN
				' . $rTable . '
			ON
				' . $cTable . '.city_id = ' . $rTable . '.city_id
			WHERE
				' . $cTable . '.city_id IS NOT NULL
			ORDER BY
				' . $cTable . '.country_code ASC,
				' . $rTable . '.city_name ASC
		';

		$output = array();
		$stmt = $this->db->query($query);
		if (!($error = self::error($stmt))) {
			while ($data = $stmt->fetchRow()) {
				$output[$data['country_code']][$data['city_id']] = $data['city_name'];
			}

			if ($caching && $this->cache && $output) {
				$this->cache->set($key, $output, $this->expiredMonth());
			}
		} else {
			$this->logError($error);
		}

		return $output;
	} // function getCitiesList

	/**
	 * Gets region code by it's name
	 *
	 * @param string $name
	 * @return string
	 */
	public static function getRegionCodeByName($name)
	{
		$name = str_replace(array(' ', "\t", '-', '.', '"', "'"), '', strtolower($name));
		$code = \MAX_commonCompressInt(crc32($name));

		return $code;
	} // function getRegionCodeByName

	/**
	 * Prepares tables before maintenance was started
	 *
	 * @param string $oldTable
	 * @param string $newTable
	 * @return bool
	 */
	public function prepareTable($oldTable, $newTable)
	{
		$newTable = $this->db->quoteIdentifier($this->config['table']['prefix'] . $newTable);
		$oldTable = $this->db->quoteIdentifier($this->config['table']['prefix'] . $oldTable);

		$query = '
			CREATE TABLE
				' . $newTable . '
			LIKE
				' . $oldTable . '
		';

		$result = $this->db->exec($query);
		if (!!($error = $this->error($result))) {
			$this->logError($error);
		}

		return !$error;
	} // function prepareTable

	/**
	 * Removes tables after maintenance was completed
	 *
	 * @param string $table
	 * @return bool
	 */
	public function removeTable($table)
	{
		$table = $this->db->quoteIdentifier($this->config['table']['prefix'] . $table);

		$query = 'DROP TABLE IF EXISTS ' . $table;

		$result = $this->db->exec($query);
		if (!!($error = $this->error($result))) {
			$this->logError($error);
		}

		return !$error;
	} // function removeTable

	/**
	 * Imports data into table while processing maintenance task
	 *
	 * @param string $table
	 * @param array $fields
	 * @param array $buffer where each element is array with key as field and value as value (your K.O.)
	 * @return bool
	 */
	public function importData($table, array $fields, array $buffer)
	{
		$table = $this->db->quoteIdentifier(
			$this->config['table']['prefix'] . $table
		);

		$db = $this->db;

		$fields = array_map(function($field) use($db) {
			return $db->quoteIdentifier($field);
		}, $fields);
		$fields = '(' . implode(', ', $fields) . ')';

		foreach ($buffer as &$values) {
			$values = array_map(function ($value) use ($db) {
				if (is_null($value)) {
					$value = 'NULL';
				} else if (!is_numeric($value)) {
					$value = $db->escape($value, true);
					$value = $db->quote($value);
				}
				return $value;
			}, $values);

			$values = '(' . implode(', ', $values) . ')';
		} // foreach
		$buffer = implode(', ', $buffer);

		$query = '
			INSERT INTO ' . $table . '
				' . $fields . '
			VALUES
				' . $buffer . '
		';

		$result = $this->db->exec($query);
		if (!!($error = $this->error($result))) {
			$this->logError($error);
		}

		return !$error;
	} // function importData
}