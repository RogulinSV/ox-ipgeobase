<?php
/**
 * Task-specific utils
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 03.02.2015 13:28
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

class TaskUtils
{
	/**
	 * Transforms name of the table according to the rule able_<date>_<uid> -> table -> table_<current_date>_<random_uid>
	 *
	 * @param string $table
	 * @return string
	 */
	public static function evolveTableName($table)
	{
		return preg_replace('/_\d{8}_[a-z0-9]{6}$/', '', $table) . '_' . date('Ymd') . '_' . \MAX_getRandomNumber(6);
	} // function evolveTableName

	/**
	 * Checks whether the changed name of the table according to the rule table_<date>_<uid>
	 *
	 * @param string $table
	 * @param array $tables
	 * @return bool
	 */
	public static function isEvolvedTableName($table, array $tables)
	{
		$tables = array_map(function($table) {
			return preg_quote($table, '/');
		}, $tables);

		return (bool)preg_match('/^(' . implode('|', $tables) . ')_\d{8}_[a-z0-9]{6}$/', $table);
	} // function isEvolvedTableName
}