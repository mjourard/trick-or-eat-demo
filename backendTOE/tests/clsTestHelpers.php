<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 1/13/2017
 * Time: 1:23 PM
 */

namespace TOETests;

use Doctrine\DBAL\Connection;
use TOE\GlobalCode\clsConstants;

class clsTestHelpers
{
	public static function GetThrowAwayEmail($uniqueId)
	{
		return clsTestConstants::THROW_AWAY_EMAIL_PREFIX . $uniqueId . clsTestConstants::THROW_AWAY_EMAIL_SUFFIX;
	}

	/**
	 * Gets the auto increment value of a table in the database.
	 *
	 * @param \Doctrine\DBAL\Connection $dbConn
	 * @param                           $tableName
	 *
	 * @return null|int Returns the auto increment value or null if the table doesn't exist or it doesn't have an auto increment value
	 */
	public static function GetAutoIncrementValueOfTable(Connection $dbConn, $tableName)
	{
		$query = "
		SELECT `AUTO_INCREMENT`
		FROM  INFORMATION_SCHEMA.TABLES
		WHERE TABLE_SCHEMA = '" . clsConstants::DATABASE_NAME . "'
		AND   TABLE_NAME   = '$tableName';
		";

		$preped = $dbConn->prepare($query);
		$result = $preped->execute();
		return empty($result) ? null : $result['AUTO_INCREMENT'];
	}
}