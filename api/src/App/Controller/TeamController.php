<?php
declare(strict_types=1);

namespace TOE\App\Controller;

use Silex\Application;
use TOE\App\Service\Event\EventManager;
use TOE\App\Service\Event\RegistrationManager;
use TOE\App\Service\Team\TeamException;
use TOE\App\Service\Team\TeamManager;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\HTTPCodes;
use TOE\GlobalCode\ResponseJson;

class TeamController extends BaseController
{
	/**
	 * Gets all team information.
	 *
	 * @param \Silex\Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getTeams(Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER, Constants::ROLE_PARTICIPANT]);

		/** @var TeamManager $teamManager */
		$teamManager = $app['team'];

		return $app->json(ResponseJson::getJsonResponseArray(true, "", ['teams' => $teamManager->getTeams()]), HTTPCodes::SUCCESS_DATA_RETRIEVED);
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
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ALL]);

		/** @var TeamManager $teamManager */
		$teamManager = $app['team'];

		//Get the teamId
		$teamInfo = $teamManager->getTeamInfo($this->userInfo->getID());
		if(empty($teamInfo) || $teamInfo['team_id'] === null)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "No team found for the logged in user"));
		}

		//Get the team information
		$teammates = $teamManager->getTeamMemberInfo($this->userInfo, $teamInfo['team_id'], $teamInfo['captain_user_id']);

		$team = [
			'id'        => $teamInfo['team_id'],
			'name'      => $teamInfo['team_name'],
			'teammates' => $teammates,
			'code'      => $teamInfo['join_code']
		];

		return $app->json(ResponseJson::getJsonResponseArray(true, "", ['team' => $team]), HTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	/**
	 * Allows the user to join a team that is registered to the same event they are registered for.
	 *
	 * @param \Silex\Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 * @throws \Doctrine\DBAL\ConnectionException
	 * @throws \TOE\App\Service\Team\TeamException
	 */
	public function joinTeam(Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER, Constants::ROLE_PARTICIPANT]);

		/** @var EventManager $eventManager */
		$eventManager = $app['event'];
		/** @var TeamManager $teamManager */
		$teamManager = $app['team'];

		//verify the user is signed up for the event
		$teamId = (int)$app[Constants::PARAMETER_KEY]['team_id'];
		$eventId = (int)$app[Constants::PARAMETER_KEY]['event_id'];
		$joinCode = $app[Constants::PARAMETER_KEY]['join_code'];
		if($eventManager->isRegistered($this->userInfo->getID(), $eventId) === false)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "User is not registered for the event the team is registered for."), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		$curTeamInfo = $teamManager->getTeamInfo($this->userInfo->getID());
		if(!empty($curTeamInfo) && $curTeamInfo['team_id'] === $eventId)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "User is already on a team for this event."), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}


		if($teamManager->isTeamAtEvent($teamId, $eventId) === false)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "The team is not signed up for the event passed in.", ["team_id" => $teamId, "event_id" => $eventId]), HTTPCodes::CLI_ERR_CONFLICT);
		}

		//verify the join code supplied was correct

		if($teamManager->isJoinCodeCorrect($teamId, $joinCode) === false)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "Incorrect code used to join the team."), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		if($teamManager->isTeamFull($teamId))
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "Team has the maximum number of members allowed on it already."), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		//assign the user to the team
		$teamManager->assignUserToTeam($this->userInfo->getID(), $teamId, $eventId);

		return $app->json(ResponseJson::getJsonResponseArray(true, ""), HTTPCodes::SUCCESS_RESOURCE_CREATED);
	}

	/**
	 * Causes the user who called the function to leave the team they are currently registered for.
	 *
	 * @param \Silex\Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 *            Returns CLI_ERROR_BAD_REQUEST when user is not on a team
	 *            Returns SUCCESS_DATA_RETRIEVED if it works as expected
	 * @throws \Doctrine\DBAL\ConnectionException
	 * @throws \TOE\App\Service\Team\TeamException
	 */
	public function leaveTeam(Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER, Constants::ROLE_PARTICIPANT]);

		/** @var TeamManager $teamManager */
		$teamManager = $app['team'];

		$teamInfo = $teamManager->getTeamInfo($this->userInfo->getID());

		//verify the person leaving a team is on a team
		if(empty($teamInfo))
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, 'User not registered to a team.'), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		//remove the user from the team
		$teamManager->removeUserFromTeam($this->userInfo->getID(), $teamInfo['event_id']);

		return $app->json(ResponseJson::getJsonResponseArray(true, ''), HTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	/**
	 * Creates a new team, setting the person who made the request as the captain of that team.
	 * Inserts placeholder members into the database to maintain the member count.
	 * Placeholder members have the characteristics specified in the request (can_drive, mobility, etc.)
	 *
	 * @param Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function createTeam(Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER, Constants::ROLE_PARTICIPANT]);
		$params = $app['params'];

		/** @var RegistrationManager $registrationManager */
		$registrationManager = $app['event.registration'];
		/** @var TeamManager $teamManager */
		$teamManager = $app['team'];

		if($params['memberCount'] > Constants::MAX_ROUTE_MEMBERS)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "Teams cannot have more than " . Constants::MAX_ROUTE_MEMBERS . " members."), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		if(preg_match(Constants::JOIN_CODE_REGEX, $params['join_code']) !== 1)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "The code to join the team must be a 3 digit code, gave: " . $params['join_code']), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		//TODO: have the event_id be passed in from the frontend and verify the user is signed up for the event
		//For now, get the event id from the user because the front end is dumb and this can't be provided yet.
		$results = $registrationManager->getEventRegisteredAtByUser($this->userInfo->getID());
		if(empty($results))
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "Not signed up for an event yet or already on a team."), HTTPCodes::CLI_ERR_NOT_AUTHORIZED);
		}

		$eventId = $results['event_id'];
		if($teamManager->isTeamNameTaken($eventId, $params['Name']))
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "Team name is taken."), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		try
		{
			$teamId = $teamManager->createTeam(
				$this->userInfo->getID(),
				$eventId,
				$params['Name'],
				$params['join_code'],
				$params['can_drive'],
				$params['hearing'],
				$params['visual'],
				$params['mobility'],
				$params['memberCount']
			);
		}
		catch(TeamException $ex)
		{
			$this->logger->err("Error while creating the team", [
				'user_id'   => $this->userInfo->getID(),
				'event_id'  => $eventId,
				'name'      => $params['Name'],
				'join_code' => $params['join_code'],
				'err'       => $ex->getMessage()
			]);

			return $app->json(ResponseJson::getJsonResponseArray(false, "An error occurred while trying to create your team. Please try again later."), HTTPCodes::SERVER_SERVICE_UNAVAILABLE);
		}

		return $app->json(ResponseJson::getJsonResponseArray(true, "", ['team_id' => $teamId, 'name' => $params['Name'], 'join_code' => $params['join_code']]), HTTPCodes::SUCCESS_RESOURCE_CREATED);
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
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER, Constants::ROLE_PARTICIPANT]);

		/** @var TeamManager $teamManager */
		$teamManager = $app['team'];
		/** @var RegistrationManager $registrationManager */
		$registrationManager = $app['event.registration'];
		if(empty($results = $registrationManager->getEventRegisteredAtByUser($this->userInfo->getID())))
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "User is not registered for an event. Register for an event to be able to check if a team name is available."), HTTPCodes::CLI_ERR_NOT_FOUND);
		}

		$taken = $teamManager->isTeamNameTaken($results['event_id'], $teamName);
		return $app->json(ResponseJson::getJsonResponseArray(true, "", ['available' => !$taken]), HTTPCodes::SUCCESS_DATA_RETRIEVED);
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
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER, Constants::ROLE_PARTICIPANT]);

		/** @var TeamManager $teamManager */
		$teamManager = $app['team'];

		//Can't kick yourself from the team
		if($this->userInfo->getID() === $app[Constants::PARAMETER_KEY]['teammate_id'])
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "Attempted to kick yourself from the team. Call the leave team function."), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		//verify the target user is on the team passed in
		if(!$teamManager->userIsOnTeam($app[Constants::PARAMETER_KEY]['teammate_id'], $app[Constants::PARAMETER_KEY]['team_id']))
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "Passed in target user is not on the passed in team"), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		//make sure the person asking to kick the teammate can kick people on that team
		$userTeamInfo = $teamManager->getTeamInfo($this->userInfo->getID());
		$teammateTeamInfo = $teamManager->getTeamInfo($app[Constants::PARAMETER_KEY]['teammate_id']);

		if(!$teamManager->userCanKick($this->userInfo, $teammateTeamInfo['captain_user_id']))
		{
			$this->logger->warn("User has attempted to kick someone without the required permissions.", [
				'user_id'            => $this->userInfo->getID(),
				'target_teammate_id' => $app[Constants::PARAMETER_KEY]['teammate_id'],
				'target_team_id'     => $teammateTeamInfo['team_id'],
				'target_captain_id'  => $teammateTeamInfo['captain_user_id']
			]);

			return $app->json(ResponseJson::getJsonResponseArray(false, 'Must be the captain of the team or have an adequate assigned role to kick someone from a team.'), HTTPCodes::CLI_ERR_NOT_AUTHORIZED);
		}

		//remove the user from the team
		try
		{
			$teamManager->removeUserFromTeam($app[Constants::PARAMETER_KEY]['teammate_id'], $teammateTeamInfo['event_id']);
		}
		catch(TeamException $ex)
		{
			$this->logger->err("Unable to kick teammate", [
					'user_id'     => $this->userInfo->getID(),
					'teammate_id' => $app[Constants::PARAMETER_KEY]['teammate_id'],
					'err'         => $ex->getMessage()
				]
			);

			return $app->json(ResponseJson::getJsonResponseArray(false, "Unable to remove the user from the team"), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		return $app->json(ResponseJson::getJsonResponseArray(true, ''), HTTPCodes::SUCCESS_DATA_RETRIEVED);
	}
}
