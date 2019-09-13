<?php

/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 11/2/2016
 * Time: 4:08 PM
 *
 * Used for interacting with a mysql database
 */
namespace TOECron;

use Exception;
use mysqli;

class clsDAL
{
    private $hostIP;
    private $port;
    private $user;
    private $password;
    private $defaultDBName;

	/** @var  mysqli */
    private $mysqli;
    private $inTransaction;

	public function getConn()
	{
		return $this->mysqli;
	}

    public function __construct($user, $password, $hostIP = "127.0.0.1", $defaultDBName = "", $port = 3306)
    {
        $this->mysqli = new mysqli($hostIP, $user, $password, $defaultDBName, $port);
        if ($this->mysqli->connect_errno) {
            throw new Exception("Failed to connect to MySQL: {$this->mysqli->connect_error}");
        }
        if ($this->mysqli === null)
		{
			throw new Exception("mysqli was null");
		}

        $this->user = $user;
        $this->password = $password;
        $this->hostIP = $hostIP;
        $this->port = $port;
        $this->defaultDBName = $defaultDBName;
        $this->inTransaction = false;

        $this->SetDefaultDatabaseName($defaultDBName);
    }

    public function SetDefaultDatabaseName($dbName)
    {
        if (is_string($dbName) && $this->defaultDBName !== $dbName)
        {
            $this->defaultDBName = $dbName;
            $this->mysqli->select_db($dbName);
        }
    }

    public function Query($query)
    {
        $res = $this->mysqli->query($query);
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function QueryFirstDBRow($query)
    {
        $res = $this->mysqli->query($query);
        if ($res === false)
        {
            return false;
        }
        $assoc = $res->fetch_assoc();
		if ($assoc === null)
		{
			return false;
		}
        return $assoc;
    }

    public function ExecuteNonQuery($query)
    {
        $this->mysqli->query($query);
		if (!empty($this->mysqli->error_list))
		{
			throw new Exception($this->mysqli->error);
		}
        return $this->mysqli->affected_rows;
    }

	/**
	 * @return int
	 */
    public function GetLastInsertedIds()
    {
		return (int)$this->mysqli->insert_id;
    }

    public function BeginTransaction()
    {
        $this->inTransaction = $this->mysqli->begin_transaction();
        return $this->inTransaction;
    }

    public function CommitTransaction()
    {
        if (!$this->inTransaction)
        {
                throw new Exception("No transaction in progress.");
        }
        $this->inTransaction = false;
        return $this->mysqli->commit();
    }

    public function RollbackTransaction()
    {
        if (!$this->inTransaction)
        {
            throw new Exception("No transaction in progress.");
        }
        $this->inTransaction = false;
        return $this->mysqli->rollback();
    }

    public function EscapeString($string)
    {
        return $this->mysqli->real_escape_string($string);
    }

    public function GetCharSet()
	{
		return $this->mysqli->get_charset();
	}
}