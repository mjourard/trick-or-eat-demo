<?php
namespace TOE\App\Controller;

use Silex\Application;
use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsHTTPCodes;
use TOE\GlobalCode\clsResponseJson;

class TeamController extends BaseController
{
	const PLACEHOLDER_EMAIL = 'toeholder.com';

	/**
	 * Gets all team information.
	 *
	 * @param \Silex\Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getTeams(Application $app)
	{
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER, clsConstants::ROLE_PARTICIPANT]);
		//Get all teams
		$qb = $this->db->createQueryBuilder();
		$qb->select(
			't.team_id',
			't.event_id',
			't.name',
			'COUNT(*) as count'
		)
			->from('team', 't')
			->leftJoin('t', 'member', 'm', 't.team_id = m.team_id')
			->groupBy('team_id');

		$results = $qb->execute()->fetchAll();
		foreach ($results as &$team)
		{
			$team['team_id'] = (int)$team['team_id'];
		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ['teams' => $results]), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	/**
	 * Gets a list of your teammate's information.
	 *
	 * Information includes:
	 * their names,
	 * arrival status,
	 * if they are captain,
	 * if they have any disabilities.
	 *
	 * Does not include placeholder information
	 *
	 * @param \Silex\Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getTeam(Application $app)
	{
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ALL]);

		//Get the teamId
		$userId = $this->userInfo->getID();
		$qb = $this->db->createQueryBuilder();
		$qb->select(
			't.team_id',
			't.name as team_name',
			't.captain_user_id',
			't.join_code'
		)
			->from('member', 'm')
			->leftJoin('m', 'team', 't', 'm.team_id = t.team_id')
			->where('m.user_id = :userId')
			->setParameter(':userId', $userId, clsConstants::SILEX_PARAM_INT);

		$teamInfo = $qb->execute()->fetch();
		if (empty($teamInfo) || $teamInfo['team_id'] === null)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "No team found for the logged in user"));
		}
		$teamId = $teamInfo['team_id'];

		//Get the team information
		$qb = $this->db->createQueryBuilder();
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
			->where("t.team_id = $teamId")
			->andWhere("email NOT like '%@" . clsConstants::PLACEHOLDER_EMAIL . "'")
			->orderBy('is_captain', 'DESC');

		$results = $qb->execute()->fetchAll();

		$kickPowers = $teamInfo['captain_user_id'] == $this->userInfo->getID() || $this->userInfo->hasRole(clsConstants::ROLE_ADMIN) || $this->userInfo->hasRole(clsConstants::ROLE_ORGANIZER);
		foreach ($results as &$row)
		{
			$row['is_captain'] = $row['is_captain'] === 'true';
			$row['can_kick'] = $kickPowers && $row['user_id'] != $this->userInfo->getID();
			$row['checked_in'] = $row['checked_in'] === 'true';
			$row['can_drive'] = $row['can_drive'] === 'true';
			$row['hearing'] = $row['hearing'] === 'true';
			$row['visual'] = $row['visual'] === 'true';
			$row['mobility'] = $row['mobility'] === 'true';
		}

		$team = [
			'name'      => $teamInfo['team_name'],
			'teammates' => $results,
			'code'      => $teamInfo['join_code']
		];

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ['team' => $team]), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	/**
	 * Allows the user to join a team that is registered to the same event they are registered for.
	 *
	 * @param \Silex\Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function joinTeam(Application $app)
	{
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER, clsConstants::ROLE_PARTICIPANT]);

		//verify the user is signed up for the event
		$teamId = $app[clsConstants::PARAMETER_KEY]['team_id'];
		$eventId = $app[clsConstants::PARAMETER_KEY]['event_id'];
		$joinCode = $app[clsConstants::PARAMETER_KEY]['join_code'];
		$results = $this->getEventRegisteredAtByUser();
		if (empty($results) || $results['event_id'] != $eventId)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "User is not registered for the event the team is on or they are already on a team."), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		if ($this->isTeamAtEvent($teamId, $eventId) === false)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "The team is not signed up for the event passed in.", ["team_id" => $teamId, "event_id" => $eventId]), clsHTTPCodes::CLI_ERR_CONFLICT);
		}

		//verify the join code supplied was correct
		$qb = $this->db->createQueryBuilder();
		$qb->select('team_id')
			->from('team')
			->where('team_id = :teamId')
			->andWhere('join_code = :joinCode')
			->setParameter(':teamId', $teamId)
			->setParameter(':joinCode', $joinCode, clsConstants::SILEX_PARAM_STRING);

		$result = $qb->execute()->fetch();
		if (empty($result))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Incorrect code used to join the team."), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		//verify the team isn't full
		$qb = $this->db->createQueryBuilder();
		$qb->select('COUNT(*) as cnt')
			->from('member')
			->where('team_id = :teamId')
			->groupBy('team_id')
			->setParameter(':teamId', $teamId);
		$result = $qb->execute()->fetch();
		if (!empty($results) && $result['cnt'] >= clsConstants::MAX_ROUTE_MEMBERS)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Team has the maximum number of members allowed on it already."), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		//assign the user to the team
		$qb = $this->db->createQueryBuilder();
		$qb->update('member')
			->set('team_id', ':teamId')
			->where('user_id = :userId')
			->andWhere('event_id = :eventId')
			->setParameter(':teamId', $teamId)
			->setParameter(':userId', $this->userInfo->getID())
			->setParameter(':eventId', $eventId);

		$exec = $qb->execute();

		if ($exec === 0)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, 'There was a problem executing the update query: ' . print_r($exec, true)), clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		}

		//remove a temporary holder from the team.
		$qb = $this->db->createQueryBuilder();
		$qb->select('m.user_id')
			->from('member', 'm')
			->leftJoin('m', 'user', 'u', 'm.user_id = u.user_id')
			->where('m.team_id = :teamId')
			->andWhere("u.first_name = '" . clsConstants::USER_PLACEHOLDER_FIRST_NAME . "'")
			->andWhere("u.last_name = '" . clsConstants::USER_PLACEHOLDER_LAST_NAME . "'")
			->orderBy('u.email', 'DESC')
			->setParameter(':teamId', $app['params']['team_id']);

		$results = $qb->execute()->fetchAll();

		if (!empty($results))
		{
			$holderId = $results[0]['user_id'];
			$qb = $this->db->createQueryBuilder();

			$qb->delete('user')
				->where("user_id = $holderId");

			if ($qb->execute() === 0)
			{
				return $app->json(clsResponseJson::GetJsonResponseArray(false, 'There was a problem removing the placeholder member from the team.'), clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
			}
		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, ""), clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
	}

	/**
	 * Causes the user who called the function to leave the team they are currently registered for.
	 *
	 * @param \Silex\Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 *            Returns CLI_ERROR_BAD_REQUEST when user is not on a team
	 *            Returns CLI_ERR_ACTION_NOT_ALLOWED if the user is on multiple teams
	 *            Returns SERVER_ERROR_GENERIC_DATABASE_FAILURE if there was a problem adding the placeholder user
	 *            Returns SUCCESS_DATA_RETRIEVED if it works as expected
	 */
	public function leaveTeam(Application $app)
	{
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER, clsConstants::ROLE_PARTICIPANT]);

		$qb = $this->db->createQueryBuilder();
		$qb->select(
			't.captain_user_id',
			't.team_id',
			't.event_id'
		)
			->from('team', 't')
			->leftJoin('t', 'member', 'm', 't.team_id = m.team_id')
			->where('m.user_id = :userId')
			->setParameter(':userId', $this->userInfo->getID());

		$teamInfo = $qb->execute()->fetchAll();

		//verify the person leaving a team is on a team
		if (empty($teamInfo))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, 'User not registered to a team.'), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		if (count($teamInfo) > 1)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, 'User registered to multiple teams. Contact an administrator.'), clsHTTPCodes::CLI_ERR_ACTION_NOT_ALLOWED);
		}

		//remove the user from the team
		$result = $this->removeUserFromTeam($teamInfo[0]['event_id'], $teamInfo[0]['team_id'], $this->userInfo->getID());
		if (empty($result))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(true, ''), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
		}

		return $app->json($result['data'], $result['status']);

	}

	/**
	 * Creates a new team, setting the person who made the request as the captain of that team.
	 * Inserts placeholder members into the database to maintain the member count.
	 * Placeholder members have the characteristics specified in the request (can_drive, mobility, etc.)
	 *
	 * @param \Silex\Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function createTeam(Application $app)
	{
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER, clsConstants::ROLE_PARTICIPANT]);
		$params = $app['params'];

		if ($params['memberCount'] > clsConstants::MAX_ROUTE_MEMBERS)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Teams cannot have more than " . clsConstants::MAX_ROUTE_MEMBERS . " members."), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		if (preg_match(clsConstants::JOIN_CODE_REGEX, $params['join_code']) !== 1)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "The code to join the team must be a 3 digit code, gave: " . $params['join_code']), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		//Get the event id from the user because the front end is dumb and this can't be provided yet.
		$results = $this->getEventRegisteredAtByUser();
		if (empty($results))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Not signed up for an event yet or already on a team."), clsHTTPCodes::CLI_ERR_NOT_AUTHORIZED);
		}

		$eventId = $results['event_id'];
		if ($this->isTeamNameRegisteredAtEvent($eventId, $params['Name']))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Team name is taken."), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		//TODO: a transaction should be started here, at time of writing not sure how to do that
		//Create the new team
		$qb = $this->db->createQueryBuilder();

		$qb->insert('team')
			->values([
				'event_id'        => $eventId,
				'captain_user_id' => $this->userInfo->getID(),
				'name'            => ":name",
				'join_code'       => ":join_code"
			])
			->setParameter(":name", $params['Name'], clsConstants::SILEX_PARAM_STRING)
			->setParameter(":join_code", $params['join_code'], clsConstants::SILEX_PARAM_INT);

		if ($qb->execute() === 0)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "There was a problem inserting the team into the database"), clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		}

		//Get the newly created team's information from the database
		$qb = $this->db->createQueryBuilder();
		$qb->select(
			't.team_id',
			't.name',
			't.join_code',
			'COUNT(m.user_id) as member_count'
		)
			->from('team', 't')
			->leftJoin('t', 'member', 'm', 't.team_id = m.team_id')
			->where('t.name = :name')
			->andWhere("t.event_id = $eventId")
			->groupBy('t.team_id')
			->having('member_count = 0')
			->setParameter(":name", $params['Name'], clsConstants::SILEX_PARAM_STRING);

		$results = $qb->execute()->fetchAll();

		$teamId = $results[0]['team_id'];
		$teamName = $results[0]['name'];
		$joinCode = $results[0]['join_code'];

		//assign the user to the team
		$qb = $this->db->createQueryBuilder();
		$qb->update('member')
			->set('team_id', $teamId)
			->where('user_id = ' . $this->userInfo->getID())
			->andWhere("event_id = $eventId");

		if ($qb->execute() === 0)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "There was an error in assigning the user to the team."), clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		}

		//Create false team members so the algorithm will work as intended
		$canDrive = $params['can_drive'] === true ? "true" : "false";
		$hearing = $params['hearing'] === true ? "true" : "false";
		$visual = $params['visual'] === true ? "true" : "false";
		$mobility = $params['mobility'] === true ? "true" : "false";
		$result = $this->addFalseTeammatesToTeam($eventId, $teamId, $params['memberCount'] - 1, $canDrive, $hearing, $visual, $mobility);
		if (!empty($result))
		{
			return $app->json($result['data'], $result['status']);
		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ['team_id' => $teamId, 'name' => $teamName, 'join_code' => $joinCode]), clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
	}

	/**
	 * Checks if the passed in name is available for a new team.
	 *
	 * @param \Silex\Application $app
	 * @param                    $teamName
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function isTeamNameAvailable(Application $app, $teamName)
	{
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER, clsConstants::ROLE_PARTICIPANT]);
		$qb = $this->db->createQueryBuilder();
		$qb->select('event_id')
			->from('member')
			->where('user_id = :userId')
			->setParameter(':userId', $this->userInfo->getID());

		if (empty($results = $qb->execute()->fetchAll()))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "User is not registered for an event. Register for an event to be able to check if a team name is available."), clsHTTPCodes::CLI_ERR_NOT_FOUND);
		}

		$eventId = (int)$results[0]['event_id'];

		$qb = $this->db->createQueryBuilder();
		$qb->select('team_id')
			->from('team')
			->where('name = :name')
			->andWhere('event_id = :event_id')
			->setParameter(':name', $teamName, clsConstants::SILEX_PARAM_STRING)
			->setParameter(':event_id', $eventId);

		$available = !$this->isTeamNameRegisteredAtEvent($eventId, $teamName);

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ['available' => $available]), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	/**
	 * Removes a teammate from a team. Fails if attempting to kick themselves, if they aren't a team captain, if they aren't an admin or organizer.
	 *
	 * @param \Silex\Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function kickTeammate(Application $app)
	{
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER, clsConstants::ROLE_PARTICIPANT]);

		//Can't kick yourself from the team
		if ($this->userInfo->getID() == $app[clsConstants::PARAMETER_KEY]['teammate_id'])
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Attempted to kick yourself from the team. Call the leave team function."), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		//make sure the person asking to kick the teammate is on the team
		$qb = $this->db->createQueryBuilder();
		$qb->select(
			't.captain_user_id',
			't.team_id',
			't.event_id'
		)
			->from('team', 't')
			->leftJoin('t', 'member', 'm', 't.team_id = m.team_id')
			->where('m.user_id = :userId')
			->setParameter(':userId', $this->userInfo->getID());

		$teamInfo = $qb->execute()->fetchAll();

		$qb = $this->db->createQueryBuilder();
		$qb->select(
			'team_id'
		)
			->from('member')
			->where('user_id = :userId')
			->setParameter(':userId', $app[clsConstants::PARAMETER_KEY]['teammate_id']);

		$targetTeamInfo = $qb->execute()->fetch();
		if (empty($teamInfo) || empty($targetTeamInfo) || $targetTeamInfo['team_id'] != $teamInfo[0]['team_id'])
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, 'Must be on the same team to kick someone from a team.'), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		//verify the person kicking the teammate is either an admin, an organizer or the team captain
		if (!$this->userInfo->hasRole(clsConstants::ROLE_ADMIN) && !$this->userInfo->hasRole(clsConstants::ROLE_ORGANIZER))
		{
			if (empty($teamInfo))
			{
				return $app->json(clsResponseJson::GetJsonResponseArray(false, 'User not registered to a team.'), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
			}

			if (count($teamInfo) > 1)
			{
				return $app->json(clsResponseJson::GetJsonResponseArray(false, 'User registered to multiple teams. Contact an administrator.'), clsHTTPCodes::CLI_ERR_ACTION_NOT_ALLOWED);
			}

			if ($teamInfo[0]['captain_user_id'] != $this->userInfo->getID())
			{
				return $app->json(clsResponseJson::GetJsonResponseArray(false, 'Must be either a captain of the team to kick teammates or have the role of admin or organizer.'), clsHTTPCodes::CLI_ERR_NOT_AUTHORIZED);
			}
		}

		//remove the user from the team
		$result = $this->removeUserFromTeam($teamInfo[0]['event_id'], $teamInfo[0]['team_id'], $app[clsConstants::PARAMETER_KEY]['teammate_id']);
		if (empty($result))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(true, ''), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
		}

		return $app->json($result['data'], $result['status']);

	}

	private function isTeamAtEvent($teamId, $eventId)
	{
		$qb = $this->db->createQueryBuilder();

		$qb->select('event_id')
			->from('team')
			->where('team_id = :teamId')
			->andWhere('event_id = :eventId')
			->setParameter('teamId', $teamId)
			->setParameter('eventId', $eventId);

		return !empty($qb->execute()->fetchAll());
	}

	/**
	 * Checks if the passed in name has been registered to a team at the event with the passed in event id.
	 *
	 * @param int    $eventId  The id of event being checked against
	 * @param string $teamName The name of the team being checked.
	 *
	 * @return bool true if the team name is taken, false if it is available.
	 */
	private function isTeamNameRegisteredAtEvent($eventId, $teamName)
	{
		$qb = $this->db->createQueryBuilder();
		$qb->select('team_id')
			->from('team')
			->where('name = :name')
			->andWhere('event_id = :event_id')
			->setParameter(':name', $teamName, clsConstants::SILEX_PARAM_STRING)
			->setParameter(':event_id', $eventId);

		return !empty($qb->execute()->fetchAll());
	}

	/**
	 * Gets the first event_id that the user is registered to where they aren't already on a team.
	 *
	 * @return array
	 */
	private function getEventRegisteredAtByUser()
	{
		$qb = $this->db->createQueryBuilder();
		$qb->select('event_id')
			->from('member')
			->where("user_id = {$this->userInfo->getID()}")
			->andWhere('team_id is NULL')
			->setMaxResults(1);

		return $qb->execute()->fetch();
	}

	private function getPlaceholderEmail($email, $holder)
	{
		return $email . "_" . "$holder@" . clsConstants::PLACEHOLDER_EMAIL;
	}

	/**
	 * Removes a user from a team.
	 * Adds a placeholder teammate to replace them and maintain the team size.
	 * Selects a new captain at random from the real people left if the person being kicked was the captain
	 * Deletes the team if nobody real is left.
	 *
	 * @param int $eventId
	 * @param int $teamId
	 * @param int $userId
	 *
	 * @return array
	 * @throws \Exception
	 */
	private function removeUserFromTeam($eventId, $teamId, $userId)
	{
		//check if they are on the team and if they are the captain
		$qb = $this->db->createQueryBuilder();
		$qb->select(
			'm.user_id',
			'm.can_drive',
			't.team_id',
			't.captain_user_id',
			't.name',
			'u.hearing',
			'u.visual',
			'u.mobility',
			"IF (u.first_name LIKE '%" . clsConstants::USER_PLACEHOLDER_FIRST_NAME . "%' AND u.last_name LIKE '%" . clsConstants::USER_PLACEHOLDER_LAST_NAME . "%', 1, 0) AS isPlaceHolder"
		)
			->from('member', 'm')
			->leftJoin('m', 'team', 't', 'm.team_id = t.team_id')
			->leftJoin('m', 'user', 'u', 'm.user_id = u.user_id')
			->where('t.team_id = :teamId')
			->setParameter(':teamId', $teamId, clsConstants::SILEX_PARAM_STRING);

		$teammates = $qb->execute()->fetchAll();
		$toKickTeammate = false;
		$captain = $teammates[0]['captain_user_id'] == $userId;
		$teammateIds = [
			'real'  => [],
			'false' => []
		];
		foreach ($teammates as $teammate)
		{
			if ($teammate['user_id'] == $userId)
			{
				$toKickTeammate = $teammate;
			}
			else
			{
				$teammateIds[$teammate['isPlaceHolder'] == '1' ? 'false' : 'real'][] = $teammate['user_id'];
			}
		}

		if ($toKickTeammate === false)
		{
			return [
				'data'   => clsResponseJson::GetJsonResponseArray(false, "User was not found on the team"),
				'status' => clsHTTPCodes::CLI_ERR_BAD_REQUEST
			];
		}

		//TODO: clean up the messy logic here
		$results = [];
		if (!empty($teammateIds['real']))
		{
			$results = $this->addFalseTeammatesToTeam($eventId, $teamId, 1, $toKickTeammate['can_drive'], $toKickTeammate['hearing'], $toKickTeammate['visual'], $toKickTeammate['mobility']);
		}

		if (!empty($results))
		{
			return $results;
		}

		//remove from the team
		$qb = $this->db->createQueryBuilder();
		$qb->update('member')
			->set('team_id', 'null')
			->where('user_id = :user_id')
			->setParameter(':user_id', $userId, clsConstants::SILEX_PARAM_INT);

		$qb->execute();

		//delete the team if it's empty
		if (empty($teammateIds['real']))
		{
			//delete the placeholder users
			if (!empty($teammateIds['false']))
			{
				$qb = $this->db->createQueryBuilder();
				$qb->delete('user')
					->where('user_id in (' . implode(",", $teammateIds['false']) . ')');

				$qb->execute();
			}

			$qb->delete('team')
				->where('team_id = :teamId')
				->setParameter(':teamId', $teamId);

			$qb->execute();

			return [];
		}

		//select a new captain if the person removed was the captain
		if ($captain === true)
		{
			$qb = $this->db->createQueryBuilder();
			$qb->update('team')
				->set('captain_user_id', $teammateIds['real'][0])
				->where('team_id = :teamId')
				->setParameter(':teamId', $teamId, clsConstants::SILEX_PARAM_INT);

			$qb->execute();
		}

		return [];
	}

	/**
	 * Uses prepared statements to add fake teammates to a team. Allows teams to maintain size
	 *
	 * @param        $eventId
	 * @param        $teamId
	 * @param int    $teammatesToAdd The number of teammates to add.
	 * @param string $canDrive       'true' or 'false'
	 * @param string $hearing        'true' or 'false'
	 * @param string $visual         'true' or 'false'
	 * @param string $mobility       'true' or 'false'
	 *
	 * @return array Returns an empty array on success, and an associative array with keys 'data' and 'status' that can be passed back to the user.
	 */
	private function addFalseTeammatesToTeam($eventId, $teamId, $teammatesToAdd, $canDrive, $hearing, $visual, $mobility)
	{
		if ($teammatesToAdd < 1)
		{
			return [];
		}

		//Get the 'starting index' for false teammates to add
		$qb = $this->db->createQueryBuilder();
		$qb->select(
			'count(*) as cnt'
		)
			->from('user', 'u')
			->leftJoin('u', 'member', 'm', 'u.user_id = m.user_id')
			->where('m.team_id = :teamId')
			->andWhere("u.email NOT like '%@" . clsConstants::PLACEHOLDER_EMAIL . "'")
			->setParameter(':teamId', $teamId);

		$results = $qb->execute()->fetch();
		if (empty($results))
		{
			return [
				'data'   => clsResponseJson::GetJsonResponseArray(false, 'Adding false teammates to an empty team.'),
				'status' => clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE
			];
		}
		$startingIndex = $results['cnt'];

		//Create false team members so the algorithm will work as intended
		$q = "
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
			";

		for ($i = 0; $i < $teammatesToAdd; $i++)
		{
			$email = $this->getPlaceholderEmail($teamId, $i + $startingIndex);
			$q .= ("('$email', 'tobedeleted', '" . clsConstants::USER_PLACEHOLDER_FIRST_NAME . "','" . clsConstants::USER_PLACEHOLDER_LAST_NAME . "', NOW()," . clsConstants::USER_PLACEHOLDER_REGION_ID . ",'$hearing','$visual','$mobility'),");
		}
		$q = rtrim($q, ",");
		$query = $this->db->prepare($q);
		if (!$query->execute())
		{
			return [
				'data'   => clsResponseJson::GetJsonResponseArray(false, 'There was a problem adding the fake teammates to the team.'),
				'status' => clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE
			];
		};

		//create member rows that match the newly created user Ids. First get hte user_ids
		$qb = $this->db->createQueryBuilder();
		$qb->select('u.user_id')
			->from('user', 'u')
			->leftJoin('u', 'member', 'm', 'u.user_id = m.user_id')
			->where("u.hearing  = '$hearing'")
			->andWhere("u.visual = '$visual'")
			->andWhere("u.mobility = '$mobility'")
			->andWhere('m.user_id is NULL')
			->andWhere('u.email like :email')
			->orderBy('date_joined', 'DESC')
			->setMaxResults($teammatesToAdd)
			->setParameter(':email', $this->getPlaceholderEmail($teamId, "%"), clsConstants::SILEX_PARAM_STRING);

		$results = $qb->execute()->fetchAll();

		//assign the new users their roles
		$q = "
			INSERT INTO user_role
			(
				user_id,
				role
			)
			VALUES ";

		for ($i = 0; $i < $teammatesToAdd; $i++)
		{
			$q .= ("({$results[$i]['user_id']},'" . clsConstants::ROLE_PARTICIPANT . "'),");
		}
		$q = rtrim($q, ",");
		$query = $this->db->prepare($q);
		if (!$query->execute())
		{
			return [
				'data'   => clsResponseJson::GetJsonResponseArray(false, "There was a problem adding the fake teammates's roles."),
				'status' => clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE
			];
		};

		//create the new members
		$q = "
			INSERT INTO member
			(
				user_id,
				team_id,
				date_joined_team,
				checked_in,
				event_id,
				can_drive
			)
			VALUES";
		for ($i = 0; $i < $teammatesToAdd; $i++)
		{
			$q .= ("({$results[$i]['user_id']},$teamId, NOW(), 'true', $eventId, '$canDrive'),");
		}
		$q = rtrim($q, ",");
		$query = $this->db->prepare($q);
		if (!$query->execute())
		{
			return [
				'data'   => clsResponseJson::GetJsonResponseArray(false, 'There was a problem adding the fake teammates to the member table.'),
				'status' => clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE
			];
		};

		return [];
	}
}
