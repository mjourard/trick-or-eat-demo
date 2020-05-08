<?php

/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 11/2/2016
 * Time: 4:08 PM
 *
 * Used for interacting with a mysql database
 */

namespace TOE\App;

use PDO;

class DAL
{
	private $hostIP;
	private $port;
	private $user;
	private $password;
	private $defaultDBName;
	private $charset;

	/** @var  PDO */
	private $pdo;
	private $inTransaction;

	public function __construct($user, $password, $hostIP = "127.0.0.1", $defaultDBName = "", $port = 3306, $charset = 'utf8mb4')
	{
		$dsn = "mysql:host=$hostIP;dbname=$defaultDBName;port=$port;charset=$charset";
		$options = [
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false
		];
		$this->pdo = new PDO($dsn, $user, $password, $options);
		$this->user = $user;
		$this->password = $password;
		$this->hostIP = $hostIP;
		$this->port = $port;
		$this->defaultDBName = $defaultDBName;
		$this->charset = $charset;
		$this->inTransaction = false;

		$this->setDefaultDatabaseName($defaultDBName);
	}

	public function setDefaultDatabaseName($dbName)
	{
		if(is_string($dbName) && $this->defaultDBName !== $dbName)
		{
			$this->defaultDBName = $dbName;
			$this->pdo->query("USE $dbName");
		}
	}

	/**
	 * Creates a prepared statement of the query and passed in values. Executes that statement and returns the fetched rows
	 *
	 * @param string $query
	 * @param array  $values
	 *
	 * @return array An array of associative arrays of ALL rows returned by the prepared query
	 * @throws DALException
	 */
	public function query($query, $values = [])
	{
		$stmt = $this->pdo->prepare($query);
		$res = $stmt->execute($values);
		if($res !== true)
		{
			throw new DALException($query, $values, "MySQL query failed");
		}

		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Creates a prepared statement of the query and passed in values. Executes that statement and returns the first fetched row
	 * @param       $query
	 * @param array $values
	 *
	 * @return array|false An associative array of the first row returned by the prepared query
	 * @throws DALException
	 */
	public function queryFirstDBRow($query, $values = [])
	{
		$stmt = $this->pdo->prepare($query);
		$res = $stmt->execute($values);
		if($res !== true)
		{
			throw new DALException($query, $values, "MySQL query failed");
		}
		$assoc = $stmt->fetch(PDO::FETCH_ASSOC);
		if($assoc === false)
		{
			return false;
		}

		return $assoc;
	}

	/**
	 * Executes a mysql statement intended to modify the database.
	 *
	 * @param       $query
	 * @param array $values
	 *
	 * @return int
	 * @throws DALException
	 */
	public function executeNonQuery($query, $values = [])
	{
		$stmt = $this->pdo->prepare($query);
		$res = $stmt->execute($values);
		if ($res === false)
		{
			throw new DALException($query, $values, "MySQL non-query statement failed");
		}
		return $stmt->rowCount();
	}

	/**
	 * Executes a raw sql statement without doing any parameter substitution. Use with caution!
	 *
	 * @param string $query A correct sql query that will modify the database
	 *
	 * @return int The number of rows affected by the sql query
	 * @throws DALException
	 */
	public function rawExecuteNonQuery($query)
	{
		$res = $this->pdo->exec($query);
		if ($res === false)
		{
			throw new DALException($query, [], "MySQL raw non-query statement failed");
		}
		return $res;
	}

	/**
	 * @return int
	 */
	public function getLastInsertedIds()
	{
		return (int)$this->pdo->lastInsertId();
	}

	/**
	 * @return bool
	 */
	public function beginTransaction()
	{
		$this->inTransaction = $this->pdo->beginTransaction();
		return $this->inTransaction;
	}

	/**
	 * @return bool
	 * @throws DALException
	 */
	public function commitTransaction()
	{
		if(!$this->inTransaction)
		{
			throw new DALException("", [], "No transaction in progress.");
		}
		$this->inTransaction = false;

		return $this->pdo->commit();
	}

	/**
	 * @return bool
	 * @throws DALException
	 */
	public function rollbackTransaction()
	{
		if(!$this->inTransaction)
		{
			throw new DALException("", [], "No transaction in progress.");
		}
		$this->inTransaction = false;

		return $this->pdo->rollBack();
	}
}