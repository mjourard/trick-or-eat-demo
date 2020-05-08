<?php
declare(strict_types=1);

namespace TOE\App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use TOE\App\Service\Event\EventManager;
use TOE\App\Service\Event\RegistrationManager;
use TOE\App\Service\Team\TeamException;
use TOE\App\Service\Team\TeamManager;
use TOE\App\Service\User\UserLookupService;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\HTTPCodes;
use TOE\GlobalCode\ResponseJson;

class EventController extends BaseController
{
	public function register(Request $request, Application $app)
	{
		/* boiler plate  */
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ALL]);
		$params = $app[Constants::PARAMETER_KEY];
		/** @var EventManager $eventManager */
		$eventManager = $app['event'];
		/** @var RegistrationManager $registrationManager */
		$registrationManager = $app['event.registration'];

		if($registrationManager->isRegistered($this->userInfo->getID(), $params['event_id']))
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, 'User is already registered for an event.'), HTTPCodes::CLI_ERR_CONFLICT);
		}

		//verify the event_id passed in is for a real event
		$event = $eventManager->getEvent($params['event_id']);
		if(empty($event))
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "There was no event with ID {$params['event_id']}."), HTTPCodes::CLI_ERR_NOT_FOUND);
		}

		/** @var UserLookupService $userLookup */
		$userLookup = $app['user.lookup'];
		$userLookup->updateAccessibilityInfo($this->userInfo->getID(), $params['mobility'], $params['visual'], $params['hearing']);

		if(!$registrationManager->registerForEvent($this->userInfo->getID(), $params['event_id'], $params['can_drive']))
		{
			$this->logger->err('unable to register user to event.', ['user_id' => $this->userInfo->getID(), 'event_id' => $params['event_id']]);

			return $app->json(ResponseJson::GetJsonResponseArray(false, 'There was a problem registering you for the event. Contact staff for the trick-or-eat event.'), HTTPCodes::SERVER_SERVICE_UNAVAILABLE);
		}

		return $app->json(ResponseJson::GetJsonResponseArray(true, "", $event), HTTPCodes::SUCCESS_RESOURCE_CREATED);
	}

	public function deregister(Request $request, Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ALL]);
		$params = $app[Constants::PARAMETER_KEY];
		/** @var EventManager $eventManager */
		$eventManager = $app['event'];
		/** @var RegistrationManager $registrationManager */
		$registrationManager = $app['event.registration'];
		if(!$registrationManager->isRegistered($this->userInfo->getID(), $params['event_id']))
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, 'The user is not registered for the event.'), HTTPCodes::CLI_ERR_ACTION_NOT_ALLOWED);
		}

		//handle the user's existing team i.e. if they are a team captain, if the team is empty without them, etc.
		/** @var TeamManager $teamManager */
		$teamManager = $app['team'];
		try
		{
			$teamManager->removeUserFromTeam($this->userInfo->getID(), $params['event_id']);
		}
		catch(TeamException $ex)
		{
			$this->logger->err("problem while deregistering a user from an event", [
				'user_id' => $this->userInfo->getID(),
				'event_id' => $params['event_id'],
				'err' => $ex->getMessage()
			]);
			return $app->json(ResponseJson::GetJsonResponseArray(false, "No rows affected when deleting member row."), HTTPCodes::SERVER_SERVICE_UNAVAILABLE);
		}

		//remove the user's member entry in the database
		if (!$eventManager->deregisterUser($this->userInfo->getID(), $params['event_id']))
		{
			$this->logger->err("Unable to delete user's row in 'member' table", [
				'user_id' => $this->userInfo->getID(),
				'event_id' => $params['event_id']
			]);
			return $app->json(ResponseJson::GetJsonResponseArray(false, "Removed user from the team but unable to deregister them from the event"), HTTPCodes::SERVER_SERVICE_UNAVAILABLE);
		}

		return $app->json(ResponseJson::GetJsonResponseArray(true, ""), HTTPCodes::SUCCESS_NO_CONTENT);
	}

	public function getEvents(Application $app, $regionId)
	{
		$this->initializeInstance($app);

		/** @var EventManager $eventManager */
		$eventManager = $app['event'];

		return $app->json(["success" => true, "events" => $eventManager->getEventsByRegion($regionId)], HTTPCodes::SUCCESS_DATA_RETRIEVED);
	}
}
