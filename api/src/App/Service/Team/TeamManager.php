<?php
declare(strict_types=1);

namespace TOE\App\Service\Team;

use Doctrine\DBAL\FetchMode;
use TOE\App\Service\BaseDBService;
use TOE\App\Service\User\UserProvider;
use TOE\GlobalCode\Constants;

//TODO: remove the queryBuilder functions in favour of creating the query builders where they are needed. Relic from a failed attempt at utilizing Aurora serverless api
class TeamManager extends BaseDBService
{
	/**
	 * gets a placeholder email address to be given to false team members, which are entities used to ensure assigning routes with teams where members are not yet signed up have been added
	 *
	 * @param $teamId
	 * @param $placeholderIndex
	 *
	 * @return string
	 */
	public function getPlaceholderEmail($teamId, $placeholderIndex)
	{
		return $teamId . "_" . "$placeholderIndex@" . Constants::PLACEHOLDER_EMAIL;
	}

	/**
	 * Gets the team that the user is registered for of the passed in event
	 *
	 * @param $userId
	 * @param $eventId
	 *
	 * @return array An associative array of the properties of the team that the user is registered for
	 */
	public function getTeamOfRegisteredUser($userId, $eventId)
	{
		$qb = $this->getTeamOfRegUserQB($userId, $eventId);
		$results = $qb->execute()->fetch(FetchMode::ASSOCIATIVE);
		if(empty($results['team_id']))
		{
			return [];
		}
		$results['team_id'] = (int)$results['team_id'];
		$results['event_id'] = (int)$results['event_id'];
		$results['captain_user_id'] = (int)$results['captain_user_id'];

		return $results;
	}

	/**
	 * Removes a user from the team
	 *
	 * @param $userId
	 * @param $eventId
	 *
	 * @throws TeamException If there was an issue removing the user from the team
	 * @throws \Doctrine\DBAL\ConnectionException If there was an issue beginning the transaction to the database
	 */
	public function removeUserFromTeam(int $userId, $eventId)
	{
		$this->dbConn->beginTransaction();
		try
		{
			$team = $this->getTeamOfRegisteredUser($userId, $eventId);
			if(empty($team))
			{
				$this->dbConn->commit();

				return;
			}

			//remove the user from the team
			$this->getRemoveFromTeamQB($userId, $eventId)->execute();


			/**
			 * If user has a team and they are team captain, ensure someone
			 * else becomes the team captain if they have team members
			 */
			$newId = false;
			if($team['team_id'] !== null && $team['captain_user_id'] === $userId)
			{
				//change the team captain to another person on the team
				$teamMembers = $this->getTeamMemberInfoQB($team['team_id'], false)->execute()->fetchAll();
				//teamMembers is never empty because of how the return values are handled on joined tables with Doctrine
				foreach($teamMembers as $member)
				{
					if(!empty($member['user_id']) && (int)$member['user_id'] !== $userId)
					{
						$newId = $member['user_id'];
						break;
					}
				}

				//If they are the only member of the team, delete the team
				if($newId === false)
				{
					$this->deleteEmptyTeam($team['team_id']);
				}
				else
				{
					//set the team captain id to be someone else. Issue of concurrency where if both users register simultaniously, this could error out.
					$this->getUpdateTeamCaptainQB($team['team_id'], $newId)->execute();
				}
			}
			$this->dbConn->commit();
		}
		catch(\Exception $ex)
		{
			$this->dbConn->rollBack();
			throw new TeamException(get_class($ex) . ": " . $ex->getMessage());
		}
	}

	/**
	 * Checks if the passed in user is on the passed in team
	 *
	 * @param int $userId
	 * @param int $teamId
	 *
	 * @return bool
	 */
	public function userIsOnTeam(int $userId, int $teamId)
	{
		$teams = $this->getUserOnTeamQB($userId)->execute()->fetchAll();
		foreach($teams as $team)
		{
			if((int)$team['team_id'] === $teamId)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets a list of teams that do not have routes assigned to them
	 *
	 * @param int $eventId
	 *
	 * @return mixed[]
	 * @throws TeamException
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getTeamsWithoutRoutes($eventId)
	{
		$q = "
		SELECT 
			m.team_id,
			t.name AS team_name,
			t.captain_user_id,
			CASE MAX(m.can_drive = 'true')
				WHEN 1 then 'true'
                ELSE 'false' END
                AS can_drive,
			CASE MAX(u.hearing = 'true')
				WHEN 1 then 'true'
                ELSE 'false' END
                AS hearing,
			CASE MAX(u.visual = 'true')
				WHEN 1 then 'true'
                ELSE 'false' END
                AS visual,
			CASE MAX(u.mobility = 'true') 
				WHEN 1 then 'true'
                ELSE 'false' END
                AS mobility,
			count(m.team_id) AS member_count
		FROM member m
		LEFT JOIN user u
			ON m.user_id = u.user_id
		LEFT JOIN team t
			ON m.team_id = t.team_id
		LEFT JOIN team_route tr 
			ON t.team_id = tr.team_id
		LEFT JOIN route_allocation ra
			ON tr.route_allocation_id = ra.route_allocation_id
			AND ra.event_id = :event_id
		WHERE m.event_id = :event_id
		AND ra.route_allocation_id is NULL 
		GROUP BY m.team_id
		HAVING member_count > 0
		ORDER BY t.name";

		$query = $this->dbConn->prepare($q);
		$query->bindValue('event_id', $eventId);
		if(!$query->execute())
		{
			throw new TeamException('There was a problem retrieving teams with active members that do not have routes.');
		}
		$rows = $query->fetchAll();
		foreach($rows as &$row)
		{
			$row['can_drive'] = $row['can_drive'] === 'true';
			$row['hearing'] = $row['hearing'] === 'true';
			$row['visual'] = $row['visual'] === 'true';
			$row['mobility'] = $row['mobility'] === 'true';
		}

		return $rows;
	}

	/**
	 * Gets all teams, the events they are signed up for and their current member counts
	 *
	 * @return array[]
	 */
	public function getTeams()
	{
		$results = $this->getTeamsQB()->execute()->fetchAll();
		foreach($results as &$team)
		{
			$team['team_id'] = (int)$team['team_id'];
		}

		return $results;
	}

	/**
	 * Gets the team info of the passed in user
	 *
	 * @param int $userId
	 *
	 * @return mixed
	 */
	public function getTeamInfo($userId)
	{
		//TODO: fix this for when a user signs up for multiple events
		$info = $this->getTeamInfoQB($userId)->execute()->fetch();
		if(empty($info['team_id']))
		{
			return [];
		}
		$info['team_id'] = (int)$info['team_id'];
		$info['event_id'] = (int)$info['event_id'];
		$info['captain_user_id'] = (int)$info['captain_user_id'];

		return $info;
	}

	/**
	 * Gets the team membership info of the passed in user and team.
	 *
	 * Captain user id is required to determine kicking powers
	 *
	 * @param UserProvider $userInfo
	 * @param int          $teamId
	 * @param int          $captainUserid
	 *
	 * @return mixed[]
	 */
	public function getTeamMemberInfo(UserProvider $userInfo, int $teamId, int $captainUserid)
	{
		$results = $this->getTeamMemberInfoQB($teamId)->execute()->fetchAll();

		$kickPowers = $this->userCanKick($userInfo, $captainUserid);
		foreach($results as &$row)
		{
			$row['is_captain'] = $row['is_captain'] === 'true';
			//don't give the user the option to kick themselves, there's a separate endpoint for that
			$row['can_kick'] = $kickPowers && $row['user_id'] !== $userInfo->getID();
			$row['checked_in'] = $row['checked_in'] === 'true';
			$row['can_drive'] = $row['can_drive'] === 'true';
			$row['hearing'] = $row['hearing'] === 'true';
			$row['visual'] = $row['visual'] === 'true';
			$row['mobility'] = $row['mobility'] === 'true';
		}

		return $results;
	}

	/**
	 * Gets the number of false team members currently assigned to the team, which was done so that routes could be assigned to teams in which not every member was signed up
	 *
	 * @param int $teamId
	 *
	 * @return int
	 */
	public function getFalseTeamMemberCount(int $teamId)
	{
		$result = $this->getFalseTeamMemberCountQB($teamId)->execute()->fetch();

		return (int)$result['cnt'];
	}

	/**
	 * Determines if the user provided can kick from the team with the passed in captain user id
	 *
	 * @param UserProvider $userInfo
	 * @param              $captainTeamId
	 *
	 * @return bool
	 */
	public function userCanKick(UserProvider $userInfo, $captainTeamId)
	{
		return $captainTeamId === $userInfo->getID() || $userInfo->hasRole(Constants::ROLE_ADMIN) || $userInfo->hasRole(Constants::ROLE_ORGANIZER);
	}

	/**
	 * Checks if the passed in team is signed up for the passed in event
	 *
	 * @param int $teamId
	 * @param int $eventId
	 *
	 * @return bool
	 */
	public function isTeamAtEvent(int $teamId, int $eventId)
	{
		return !empty($this->isTeamAtEventQB($teamId, $eventId)->execute()->fetchAll());
	}

	/**
	 * Checks if the passed in join code is correct for the supplied team
	 *
	 * @param int    $teamId
	 * @param string $joinCode
	 *
	 * @return bool
	 */
	public function isJoinCodeCorrect($teamId, $joinCode)
	{
		return !empty($this->getJoinCodeCorrectQB($teamId, $joinCode)->execute()->fetch());
	}

	/**
	 * Checks if the passed in team is full or not
	 *
	 * @param int $teamId
	 *
	 * @return bool
	 */
	public function isTeamFull($teamId)
	{
		$result = $this->getIsTeamFullQB($teamId)->execute()->fetch();

		return !empty($result) && $result['cnt'] >= Constants::MAX_ROUTE_MEMBERS;
	}

	/**
	 * Checks if the passed in name has been registered to a team at the event with the passed in event id.
	 *
	 * @param int    $eventId  The id of event being checked against
	 * @param string $teamName The name of the team being checked.
	 *
	 * @return bool true if the team name is taken, false if it is available.
	 */
	public function isTeamNameTaken(int $eventId, string $teamName)
	{
		return !empty($this->getIsTeamNameTakenQB($eventId, $teamName)->execute()->fetchAll());
	}

	/**
	 * Assigns the passed in user to the passed in team that is signed up for the passed in event
	 *
	 * @param int $userId
	 * @param int $teamId
	 * @param int $eventId
	 *
	 * @throws TeamException
	 * @throws \Doctrine\DBAL\ConnectionException
	 */
	public function assignUserToTeam(int $userId, int $teamId, int $eventId)
	{
		$this->dbConn->beginTransaction();
		try
		{
			//assign the user to the team
			$this->getAssignUserToTeamQB($userId, $teamId, $eventId)->execute();
			//remove a temporary holder from the team.
			$teamUsers = $this->getTempUserFromTeamQB($teamId)->execute()->fetchAll();
			if(!empty($teamUsers))
			{
				$this->getDeletePlaceholderQB((int)$teamUsers[0]['user_id'])->execute();
			}
			$this->dbConn->commit();
		}
		catch(\Exception $ex)
		{
			$this->dbConn->rollBack();
			throw new TeamException($ex->getMessage());
		}
	}

	/**
	 * Creates a team given the passed in name and join code for the passed in event.
	 *
	 * The passed in user is set as the team captain
	 *
	 * Fake users and members are created equal to $totalMemberCount - 1 and they are added to the team.
	 * This is to make the assignAllRoutes function work as intended when a user's teammates have not yet registered and
	 * joined their team at the time of the assignAll function being used
	 *
	 * All fake users will have the passed in combination of accessibility constraints and ability to drive themselves to the event
	 *
	 * @param int    $userId
	 * @param int    $eventId
	 * @param string $name             The name of the team
	 * @param string $joinCode         The code to be used to join the team
	 * @param bool   $canDrive         If any of the members of the newly created team can drive themselves to their route
	 * @param bool   $hearing          If any of the members of the newly created team have any hearing impairments
	 * @param bool   $visual           If any of the members of the newly created team have any visual impairments
	 * @param bool   $mobility         If any of the members of the newly created team have any mobility impairments
	 * @param int    $totalMemberCount The total number of people that are intended to join the team
	 *
	 * @return int The id of the newly created team
	 * @throws TeamException
	 * @throws \Doctrine\DBAL\ConnectionException
	 */
	public function createTeam(int $userId, int $eventId, string $name, string $joinCode, bool $canDrive, bool $hearing, bool $visual, bool $mobility, int $totalMemberCount)
	{
		//Create the new team
		try
		{
			$this->dbConn->beginTransaction();
			$this->getInserTeamQB($userId, $eventId, $name, $joinCode)->execute();
			if(empty($this->dbConn->lastInsertId()))
			{
				throw new TeamException("There was a problem inserting the team into the database");
			}
			['team_id' => $teamId, 'name' => $teamName, 'join_code' => $joinCode, 'member_count' => $memberCount] = $this->getTeamRegistrationInfoQB($eventId, $name)->execute()->fetch();
			$teamId = (int)$teamId;
			if($this->getAssignUserToTeamQB($userId, $teamId, $eventId)->execute() === 0)
			{
				throw new TeamException("There was an error in assigning the user to the team (team name '$teamName').");
			}
			if($totalMemberCount > 1)
			{
				$this->addFalseTeammatesToTeam($eventId, $teamId, $totalMemberCount - 1, $canDrive, $hearing, $visual, $mobility);
			}
			$this->dbConn->commit();

			return $teamId;
		}
		catch(\Exception $ex)
		{
			$this->dbConn->rollBack();
			throw new TeamException($ex->getMessage());
		}
	}

	/**
	 * Deletes the passed in empty team
	 *
	 * @param int $teamId
	 *
	 * @throws TeamException
	 */
	public function deleteEmptyTeam(int $teamId)
	{
		//get the members of the team
		$members = $this->getTeamMemberInfoQB($teamId, true)->execute()->fetchAll();
		$isEmpty = true;
		$ids = [];
		foreach($members as $member)
		{
			if (empty($member['user_id']))
			{
				continue;
			}
			if(
				stripos($member['first_name'], Constants::USER_PLACEHOLDER_FIRST_NAME) === false &&
				stripos($member['last_name'], Constants::USER_PLACEHOLDER_LAST_NAME) === false
			)
			{
				$isEmpty = false;
			}
			$ids[] = $member['user_id'];
		}
		if(!$isEmpty)
		{
			throw new TeamException("Found non-placeholder members on team, therefore will not be deleting the team");
		}

		//delete the temp users
		foreach($ids as $id)
		{
			$this->getDeletePlaceholderQB((int)$id)->execute();
		}

		//delete the team
		$this->getDeleteTeamQB($teamId)->execute();
	}

	#region query builders
	protected function getTeamOfRegUserQB($userId, $eventId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			'm.team_id',
			'm.event_id',
			't.captain_user_id'
		)
			->from('member', 'm')
			->leftJoin('m', 'team', "t", 'm.team_id = t.team_id')
			->where('m.user_id = :user_id')
			->andWhere('t.event_id = :event_id OR t.event_id IS NULL')
			->setParameter(':user_id', $userId)
			->setParameter(':event_id', $eventId);

		return $qb;
	}

	protected function getOtherMembersOfTeamQB($userId, $teamId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('user_id')
			->from('member')
			->where("team_id = :team_id")
			->andWhere('user_id != :user_id')
			->setParameter(':team_id', $teamId)
			->setParameter(':user_id', $userId);

		return $qb;
	}

	protected function getDeleteTeamQB($teamId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->delete('team')
			->where("team_id = :team_id")
			->setParameter(':team_id', $teamId);

		return $qb;
	}

	protected function getUpdateTeamCaptainQB($teamId, $newCaptainId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->update('team')
			->set('captain_user_id', ':captain_user_id')
			->where("team_id = :team_id")
			->setParameter(':captain_user_id', $newCaptainId)
			->setParameter(':team_id', $teamId);

		return $qb;
	}

	protected function getRemoveFromTeamQB($userId, $eventId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->update('member')
			->set('team_id', 'NULL')
			->where('user_id = :user_id')
			->andWhere('event_id = :event_id')
			->setParameter(':user_id', $userId)
			->setParameter(':event_id', $eventId);

		return $qb;
	}

	protected function getUserOnTeamQB($userId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('team_id')
			->from('member')
			->where('user_id = :user_id')
			->setParameter(":user_id", $userId);

		return $qb;
	}

	protected function getTeamsQB()
	{
		//Get all teams
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			't.team_id',
			't.event_id',
			't.name',
			'COUNT(*) as count'
		)
			->from('team', 't')
			->leftJoin('t', 'member', 'm', 't.team_id = m.team_id')
			->groupBy('t.team_id');

		return $qb;
	}

	protected function getTeamInfoQB($userId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			't.team_id',
			't.name as team_name',
			't.captain_user_id',
			't.join_code',
			'm.event_id'
		)
			->from('member', 'm')
			->leftJoin('m', 'team', 't', 'm.team_id = t.team_id')
			->where('m.user_id = :userId')
			->setParameter(':userId', $userId, Constants::SILEX_PARAM_INT);

		return $qb;
	}

	protected function getTeamMemberInfoQB($teamId, $includeFalseMembers = false)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			"IF(t.captain_user_id = m.user_id, 'true', 'false') as is_captain",
			'm.checked_in',
			'm.can_drive',
			'm.user_id',
			'u.first_name',
			'u.last_name',
			'u.hearing',
			'u.visual',
			'u.mobility'
		)
			->from('team', 't')
			->leftJoin('t', 'member', 'm', 't.team_id = m.team_id')
			->leftJoin('m', 'user', 'u', 'm.user_id = u.user_id')
			->where("t.team_id = :team_id")
			->orderBy('is_captain', 'DESC')
			->setParameter(':team_id', $teamId);

		if(!$includeFalseMembers)
		{
			$qb->andWhere("email NOT like '%@" . Constants::PLACEHOLDER_EMAIL . "'");
		}

		return $qb;
	}

	protected function isTeamAtEventQB($teamId, $eventId)
	{
		$qb = $this->dbConn->createQueryBuilder();

		$qb->select('event_id')
			->from('team')
			->where('team_id = :teamId')
			->andWhere('event_id = :eventId')
			->setParameter('teamId', $teamId)
			->setParameter('eventId', $eventId);

		return $qb;
	}

	protected function getJoinCodeCorrectQB($teamId, $joinCode)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('team_id')
			->from('team')
			->where('team_id = :teamId')
			->andWhere('join_code = :joinCode')
			->setParameter(':teamId', $teamId)
			->setParameter(':joinCode', $joinCode, Constants::SILEX_PARAM_STRING);

		return $qb;
	}

	protected function getIsTeamFullQB($teamId)
	{
		//verify the team isn't full
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('COUNT(*) as cnt')
			->from('member')
			->where('team_id = :teamId')
			->groupBy('team_id')
			->setParameter(':teamId', $teamId);

		return $qb;
	}

	protected function getAssignUserToTeamQB(int $userId, int $teamId, int $eventId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->update('member')
			->set('team_id', ':teamId')
			->where('user_id = :userId')
			->andWhere('event_id = :eventId')
			->setParameter(':teamId', $teamId)
			->setParameter(':userId', $userId)
			->setParameter(':eventId', $eventId);

		return $qb;
	}

	protected function getTempUserFromTeamQB(int $teamId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('m.user_id')
			->from('member', 'm')
			->leftJoin('m', 'user', 'u', 'm.user_id = u.user_id')
			->where('m.team_id = :teamId')
			->andWhere("u.first_name = '" . Constants::USER_PLACEHOLDER_FIRST_NAME . "'")
			->andWhere("u.last_name = '" . Constants::USER_PLACEHOLDER_LAST_NAME . "'")
			->orderBy('u.email', 'DESC')
			->setParameter(':teamId', $teamId);

		return $qb;
	}

	protected function getDeletePlaceholderQB(int $userId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->delete('user')
			->where("user_id = :user_id")
			->andWhere('first_name = :first_name')
			->andWhere('last_name = :last_name')
			->setParameter(':user_id', $userId)
			->setParameter(':first_name', Constants::USER_PLACEHOLDER_FIRST_NAME)
			->setParameter(':last_name', Constants::USER_PLACEHOLDER_LAST_NAME);

		return $qb;
	}

	protected function getIsTeamNameTakenQB(int $eventId, string $teamName)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('team_id')
			->from('team')
			->where('name = :name')
			->andWhere('event_id = :event_id')
			->setParameter(':name', $teamName, Constants::SILEX_PARAM_STRING)
			->setParameter(':event_id', $eventId);

		return $qb;
	}

	protected function getInserTeamQB(int $userId, int $eventId, string $name, string $joinCode)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->insert('team')
			->values([
				'event_id'        => ':event_id',
				'captain_user_id' => ':user_id',
				'name'            => ":name",
				'join_code'       => ":join_code"
			])
			->setParameter(':user_id', $userId)
			->setParameter(':event_id', $eventId)
			->setParameter(":name", $name, Constants::SILEX_PARAM_STRING)
			->setParameter(":join_code", $joinCode, Constants::SILEX_PARAM_INT);

		return $qb;
	}

	/**
	 * Get the newly created team's information from the database
	 *
	 * @param int    $eventId
	 * @param string $name
	 *
	 * @return \Doctrine\DBAL\Query\QueryBuilder
	 */
	protected function getTeamRegistrationInfoQB(int $eventId, string $name)
	{

		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			't.team_id',
			't.name',
			't.join_code',
			'COUNT(m.user_id) as member_count'
		)
			->from('team', 't')
			->leftJoin('t', 'member', 'm', 't.team_id = m.team_id')
			->where("t.event_id = :event_id")
			->andWhere('t.name = :name')
			->groupBy('t.team_id')
			->having('member_count = 0')
			->setParameter(':event_id', $eventId)
			->setParameter(":name", $name, Constants::SILEX_PARAM_STRING);

		return $qb;
	}

	protected function getFalseTeamMemberCountQB(int $teamId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			'count(*) as cnt'
		)
			->from('user', 'u')
			->leftJoin('u', 'member', 'm', 'u.user_id = m.user_id')
			->where('m.team_id = :team_id')
			->andWhere("u.email NOT like '%@" . Constants::PLACEHOLDER_EMAIL . "'")
			->setParameter(':team_id', $teamId);

		return $qb;
	}

	protected function getRecentFalseUserIdsQB(int $teamId, int $teammatesToAdd)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('u.user_id')
			->from('user', 'u')
			->leftJoin('u', 'member', 'm', 'u.user_id = m.user_id')
			->where('m.user_id is NULL')
			->andWhere('u.email like :email')
			->orderBy('date_joined', 'DESC')
			->setMaxResults($teammatesToAdd)
			->setParameter(':email', $this->getPlaceholderEmail($teamId, "%"), Constants::SILEX_PARAM_STRING);

		return $qb;
	}
	#endregion

	#region raw queries
	/**
	 * Gets a query for inserting a number of false users into the user table for calculating future team sizes
	 *
	 * @param      $teamId
	 * @param int  $usersToAdd
	 * @param int  $startingIndex
	 * @param bool $hearing
	 * @param bool $visual
	 * @param bool $mobility
	 *
	 * @return string a raw mysql query that can be executed without having to provide any substitutions
	 */
	protected function getInsertFalseUsersQuery($teamId, int $usersToAdd, int $startingIndex, bool $hearing, bool $visual, bool $mobility)
	{
		//Create false team members so the algorithm will work as intended
		$values = [];
		for($i = 0; $i < $usersToAdd; $i++)
		{
			//password is unencrypted 'tobedeleted' because the false user should never be logged in legitimately and peaking at the database should reveal the user can be deleted if errors arrise
			$values[] = sprintf("('%s','tobedeleted','%s','%s',CURRENT_TIMESTAMP,%d,'%s','%s','%s')",
				$this->getPlaceholderEmail($teamId, $i + $startingIndex),
				Constants::USER_PLACEHOLDER_FIRST_NAME,
				Constants::USER_PLACEHOLDER_LAST_NAME,
				Constants::USER_PLACEHOLDER_REGION_ID,
				$hearing ? 'true' : 'false',
				$visual ? 'true' : 'false',
				$mobility ? 'true' : 'false'
			);
		}
		$valuesStr = implode(",", $values);

		return "
			INSERT INTO user
			(
				email,
				password,
				first_name,
				last_name,
				date_joined,
				region_id,
				hearing,
				visual,
				mobility				
			)
			VALUES
			$valuesStr
			";
	}

	/**
	 * inserts user participant user roles into the database for the passed in array of rows of user_ids
	 *
	 * Intended to only be used with newly created false users
	 *
	 * @param $userIdRows
	 *
	 * @return string
	 */
	protected function getInsertFalseUserRolesQuery($userIdRows)
	{
		$values = [];
		foreach($userIdRows as $row)
		{
			$values[] = sprintf("(%s,'%s')", $row['user_id'], Constants::ROLE_PARTICIPANT);
		}
		$valuesStr = implode(",", $values);

		//assign the new users their roles
		return "
			INSERT INTO user_role
			(
				user_id,
				role
			)
			VALUES 
			$valuesStr";
	}

	protected function getInsertFalseMembersQuery($falseUserIdRows, int $teamId, int $eventId, bool $canDrive)
	{
		$values = [];
		foreach($falseUserIdRows as $row)
		{
			$values[] = sprintf("(%s,%d,CURRENT_TIMESTAMP,'true',%d,'%s')",
				$row['user_id'],
				$teamId,
				$eventId,
				$canDrive ? 'true' : 'false'
			);
		}
		$valuesStr = implode(",", $values);

		return "
			INSERT INTO member
			(
				user_id,
				team_id,
				date_joined_team,
				checked_in,
				event_id,
				can_drive
			)
			VALUES
			$valuesStr";
	}

	#endregion

	/**
	 * Uses prepared statements to add fake teammates to a team. Allows teams to maintain size
	 *
	 * @param        $eventId
	 * @param        $teamId
	 * @param int    $teammatesToAdd The number of teammates to add.
	 * @param bool   $canDrive
	 * @param bool   $hearing
	 * @param bool   $visual
	 * @param bool   $mobility
	 *
	 * @throws TeamException|\Doctrine\DBAL\DBALException
	 */
	protected function addFalseTeammatesToTeam($eventId, $teamId, $teammatesToAdd, bool $canDrive, bool $hearing, bool $visual, bool $mobility)
	{
		if($teammatesToAdd < 1)
		{
			throw new TeamException("Attempted to add less than 1 teammate for team $teamId");
		}

		//Get the 'starting index' for false teammates to add
		$startingIndex = $this->getFalseTeamMemberCount($teamId);

		$q = $this->getInsertFalseUsersQuery($teamId, $teammatesToAdd, $startingIndex, $hearing, $visual, $mobility);
		$query = $this->dbConn->prepare($q);
		if(!$query->execute())
		{
			throw new TeamException('There was a problem adding the fake teammates to the team: ' . print_r($query->errorInfo(), true));
		}

		//create member rows that match the newly created user Ids. First get the user_ids
		$falseUserIdRows = $this->getRecentFalseUserIdsQB($teamId, $teammatesToAdd)->execute()->fetchAll();
		if(count($falseUserIdRows) !== $teammatesToAdd)
		{
			throw new TeamException("count of returned false user ids did not match number of teammates to add; something went wrong when inserting into the user table");
		}

		$q = $this->getInsertFalseUserRolesQuery($falseUserIdRows);
		$query = $this->dbConn->prepare($q);
		if(!$query->execute())
		{
			throw new TeamException("There was a problem adding the fake teammates's roles.");
		}

		//create the new members
		$q = $this->getInsertFalseMembersQuery($falseUserIdRows, $teamId, $eventId, $canDrive);
		$query = $this->dbConn->prepare($q);
		if(!$query->execute())
		{
			throw new TeamException('There was a problem adding the fake teammates to the member table.');
		}
	}

}