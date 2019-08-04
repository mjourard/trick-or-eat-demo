<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 10/28/2017
 * Time: 12:34 PM
 */

namespace TOECron\RegistrationImport;

use Exception;
use TOECron\clsDAL;

class User
{
	/** @var  int */
	private $userId;
	private $firstName;
	private $lastName;
	private $email;
	private $regDate;
	private $dryRun;

	public function __construct($firstName, $lastName, $email, $regDate, $dryRun, clsDAL $DAL)
	{
		$this->firstName = $firstName;
		$this->lastName = $lastName;
		$this->email = $email;
		$this->regDate = $regDate;
		$this->dryRun = $dryRun;
		$this->DAL = $DAL;
	}

	public function getFullName()
	{
		return $this->firstName . " " . $this->lastName;
	}

	public function addUser()
	{
		$query = "
		INSERT INTO user (
			email,
			password,
			first_name,
			last_name,
			date_joined,
			region_id,
			hearing,
			visual,
			mobility
		) values " . $this->getInsertValues();

		$this->execQuery($query);
		$userId = $this->DAL->GetLastInsertedIds();
		$this->userId = $userId;

		$query = "
		INSERT INTO user_role (user_id, role)
		VALUES ($userId, 'participant')
		";
		$this->execQuery($query);
	}

	public function registerForEvent($eventId)
	{
		$query = "
		INSERT INTO MEMBER 
		(
			user_id,
			can_drive,
			event_id
		) 
		VALUES 
		(
			{$this->userId},
			'false',
			$eventId
		)";
		$this->execQuery($query);
	}

	public function joinTeam($teamId, $dateJoined)
	{
		$query = "
		UPDATE MEMBER SET 
			team_id = $teamId, 
			date_joined_team = '$dateJoined' 
		WHERE user_id = {$this->userId}";
		$this->execQuery($query);
	}

	/**
	 * @return int
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	public function getInsertValues()
	{
		$password = uniqid();
		$details = implode([
			$this->DAL->EscapeString($this->email),
			$password,
			$this->DAL->EscapeString($this->firstName),
			$this->DAL->EscapeString($this->lastName),
			$this->DAL->EscapeString($this->regDate)
		], "','");
		return "('$details',9,'false','false','false')";
	}

	private function execQuery($query)
	{
		if ($this->dryRun !== true)
		{
			try
			{
				return $this->DAL->ExecuteNonQuery($query);
			}
			catch (Exception $ex)
			{
				die("Failed Query: $query\n");

			}
		}
		echo $query;
		return false;
	}

}