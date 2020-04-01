<?php
namespace TOE\App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsHTTPCodes;
use TOE\GlobalCode\clsResponseJson;

class EventController extends BaseController
{
	public function Register(Request $request, Application $app)
	{
		/* boiler plate  */
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ALL]);
		$params = $app[clsConstants::PARAMETER_KEY];

		if ($this->IsRegistered())
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, 'User is already registered for an event. '), clsHTTPCodes::CLI_ERR_CONFLICT);
		}

		//verify the event_id passed in is for a real event
		$qb = $this->db->createQueryBuilder();
		$qb->select('event_id', 'event_name')
			->from('event')
			->where('event_id = :event_id')
			->setParameter(':event_id', $params['event_id']);

		$event = $qb->execute()->fetchAll();
		if (empty($event))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "There was no event with ID {$params['event_id']}."), clsHTTPCodes::CLI_ERR_NOT_FOUND);
		}
		$event = $event[0];
		$event['event_id'] = (int)$event['event_id'];

		//TODO: investigate how to properly use the true/false enum with doctrine, as this is super ugly
		$mobility = $params['mobility'] === true ? 'true' : 'false';
		$visual = $params['visual'] === true ? 'true' : 'false';
		$hearing = $params['hearing'] === true ? 'true' : 'false';
		#insert user data into DB
		$qb = $this->db->createQueryBuilder();
		$qb->update('user')
			->set('mobility', ':mobility')
			->set('visual', ':visual')
			->set('hearing', ':hearing')
			->where('user_id = :user_id')
			->setParameter(':mobility', $mobility)
			->setParameter(':visual', $visual)
			->setParameter(':hearing', $hearing)
			->setParameter(':user_id', $this->userInfo->getID());

		$qb->execute();

		$canDrive = $params['can_drive'] === true ? 'true' : 'false';
		#insert user data into DB
		$q = "
            INSERT INTO member 
            (
              user_id, 
              can_drive, 
              event_id
            ) 
            VALUES 
            (
              :user_id, 
              '$canDrive', 
              :event_id
            ) 
            ON DUPLICATE KEY UPDATE 
              can_drive = values(can_drive), 
              event_id = values(event_id)";
		$query = $this->db->prepare($q);
		$query->bindValue(':user_id', $this->userInfo->getID());
		$query->bindValue(':event_id', $params['event_id']);
		if (!$query->execute())
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, 'There was a problem executing the insert query'), clsHTTPCodes::SERVER_GENERIC_ERROR);
		};

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", $event), clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
	}

	public function Deregister(Request $request, Application $app)
	{
		/* boiler plate */
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ALL]);
		$params = $app[clsConstants::PARAMETER_KEY];

		if (!$this->IsRegistered())
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, 'The user is not registered for an event. '), clsHTTPCodes::CLI_ERR_ACTION_NOT_ALLOWED);
		}

		//Get the team data that the user is on.
		$qb = $this->db->createQueryBuilder();
		$qb->select(
				'm.team_id',
				'm.event_id',
				't.captain_user_id'
			)
			->from('member', 'm')
			->leftJoin('m', 'team', "t", 'm.team_id = t.team_id')
			->where('m.user_id = :user_id')
			->andWhere('t.event_id = :event_id OR t.event_id IS NULL')
			->setParameter(':user_id', $this->userInfo->getID())
			->setParameter(':event_id', $params['event_id']);
		$results = $qb->execute()->fetchAll();

		if (empty($results))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "The user was not registered for the event passed in."), clsHTTPCodes::CLI_ERR_ACTION_NOT_ALLOWED);
		}

		$teamId = $results[0]['team_id'];

		//If user has a team and they are team captain:
		if ($teamId !== null && $results[0]['captain_user_id'] === $this->userInfo->getID())
		{
			//change the team captain to another person on the team
			$qb = $this->db->createQueryBuilder();
			$qb->select('user_id')
				->from('member')
				->where("team_id = $teamId")
				->andWhere('user_id != ' . $this->userInfo->getID());
			$results = $qb->execute()->fetchAll();

			$newId = !empty($results) ? $results[0]['user_id'] : false;

			//If they are the only member of the team, delete the team
			if ($newId === false)
			{
				$qb = $this->db->createQueryBuilder();
				$qb->delete('team')
					->where("team_id = $teamId");
				$qb->execute();
			}
			else
			{
				//set the team captain id to be someone else. Issue of concurrency where if both users register simultaniously, this could error out.
				$qb = $this->db->createQueryBuilder();
				$qb->update('team')
					->set('captain_user_id', $newId)
					->where("team_id = $teamId");
				$qb->execute();
			}
		}

		$qb = $this->db->createQueryBuilder();
		$qb->delete('member')
			->where('user_id = :user_id')
			->andWhere('event_id = :event_id')
			->setParameter(':user_id', $this->userInfo->getID())
			->setParameter(':event_id', $params['event_id']);

		if ($qb->execute() === 0)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "No rows affected when deleting member row."), clsHTTPCodes::SERVER_GENERIC_ERROR);
		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, ""), clsHTTPCodes::SUCCESS_NO_CONTENT);
	}

	public function GetEvents(Application $app, $regionId)
	{
		$this->InitializeInstance($app);

		$qb = $this->db->createQueryBuilder();
		$qb->select('event_id', 'event_name')
			->from('event')
			->where('region_id = :region_id')
			->setParameter(':region_id', $regionId);

		$results = $qb->execute()->fetchAll();

		foreach ($results as &$event)
		{
			$event['event_id'] = (int)$event['event_id'];
		}

		return $app->json(["success" => true, "events" => $results], clsHTTPCodes::SUCCESS_DATA_RETRIEVED);

	}

	#check if user is registered for a different event
	private function IsRegistered()
	{
		$userID = $this->userInfo->getID();
		$qb = $this->db->createQueryBuilder();
		$qb->select('event_id')
			->from('member')
			->where("user_id = :user_id")
			->setParameter(':user_id', $userID);

		return !empty($qb->execute()->fetchAll());
	}
}
