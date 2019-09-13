<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 10/28/2017
 * Time: 12:37 PM
 */

namespace TOECron\RegistrationImport;

use Exception;
use TOECron\clsDAL;

class Team
{
	const MAX_TEAM_SIZE = 6;

	/**
	 * @var User[]
	 */
	private $teammates = [];

	/** @var  string */
	private $capName;

	/** @var  User */
	private $captain;

	/** @var  int[] */
	private $teamIds;

	private $dryRun;

	private $DAL;

	public function __construct($name, $capName, $regDate, $dryRun, clsDAL $DAL)
	{
		$this->name = $name;
		$this->capName = $capName;
		$this->regDate = $regDate;
		$this->dryRun = $dryRun;
		$this->DAL = $DAL;
		$this->teamIds = [];
	}

	public function addTeammate(User $user, $dateJoined)
	{
		$this->teammates[] = [
			'user'       => $user,
			'dateJoined' => $dateJoined
		];
		if ($user->getFullName() === $this->capName)
		{
			$this->captain = $user;
		}
	}

	public function createTeam($eventId)
	{
		$joinCode = (string)(rand(0, 9) * 100 + rand(0, 9) * 10 + rand(0, 9));
		$teams = (int)floor((count($this->teammates) / self::MAX_TEAM_SIZE)) + (count($this->teammates) % self::MAX_TEAM_SIZE === 0 ? 0 : 1);
		$startIndex = 0;
		$endIndex = self::MAX_TEAM_SIZE - 1;

		for ($i = 1; $i <= $teams; $i++)
		{
			$teamName = $this->name;
			$teamName .= ($teams === 1 ? '' : "-$i");
			$query = "
			INSERT INTO TEAM
			(
				event_id,
				captain_user_id,
				name,
				join_code
			)
			VALUES
			(
				$eventId,
				{$this->captain->getUserId()},
				'" . $this->DAL->EscapeString($teamName) . "',
				'$joinCode'
			)";

			$this->execQuery($query);
			$teamId = $this->DAL->GetLastInsertedIds();
			$this->teamIds[] = $teamId;
			$this->addTemmatesToTeam($teamId, $startIndex, $endIndex);
			$startIndex += self::MAX_TEAM_SIZE;
			$endIndex += self::MAX_TEAM_SIZE;
		}

	}

	private function addTemmatesToTeam($teamId, $startIndex, $endIndex = -1)
	{
		if ($endIndex === -1)
		{
			$endIndex = count($this->teammates) - 1;
		}

		if ($endIndex - $startIndex > self::MAX_TEAM_SIZE)
		{
			$endIndex = $startIndex + self::MAX_TEAM_SIZE - 1;
		}

		if ($endIndex > count($this->teammates) - 1)
		{
			$endIndex = count($this->teammates) - 1;
		}

		for($i = $startIndex; $i <= $endIndex; $i++)
		{
			/** @var User $user */
			$user = $this->teammates[$i]['user'];
			$user->joinTeam($teamId, $this->teammates[$i]['dateJoined']);
		}
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
				die("Failed Query: \n$query");
			}
		}
		echo $query;
		return false;
	}


}