<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 11/11/2016
 * Time: 11:01 AM
 */

namespace TOETests\App\Controller;

use TOE\GlobalCode\clsHTTPCodes;
use TOETests\BaseTestCase;
use TOETests\clsTesterCreds;

class EventControllerTest extends BaseTestCase
{
	const REGION_ID_WITH_EVENT   = 9;
	const REGION_NAME_WITH_EVENT = "Ontario";

	const REGION_ID_WITHOUT_EVENT   = 1;
	const REGION_NAME_WITHOUT_EVENT = "Alberta";

	const TEST_EVENT_ID   = 1;
	const TEST_EVENT_NAME = "Trick-or-Eat-2016";
	const BAD_EVENT_ID    = 999999999;

	/**
	 * @group Event
	 */
	public function testGetEvents()
	{
		$this->SetClient();

		$this->Login(clsTesterCreds::NORMAL_USER_EMAIL);

		//Test getting events for ontario, there should be at least one event in the test database
		$this->client->request('GET', "/events/" . self::REGION_ID_WITH_EVENT);

		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
		$contents = json_decode($this->lastResponse->getContent());

		$this->assertNotEmpty($contents->events, self::REGION_NAME_WITH_EVENT . " did not contain any events");

		//Check to make sure the formatting of the events objects is consistent
		foreach ($contents->events as $event)
		{
			$this->assertNotNull($event->event_name, "event object in " . self::REGION_NAME_WITH_EVENT . " did not contain the 'event_name' property.");
			$this->assertNotNull($event->event_id, "event object in " . self::REGION_NAME_WITH_EVENT . " did not contain the 'event_id' property");
		}

		//Test getting events in a region that has no events
		$this->client->request('GET', "/events/" . self::REGION_ID_WITHOUT_EVENT);

		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);

		$contents = json_decode($this->lastResponse->getContent());

		$this->assertEmpty($contents->events);
	}

	/**
	 * @group Event
	 * The register event function registers a user for an event
	 */
	public function testRegister()
	{
		$this->SetClient();

		$this->Login(clsTesterCreds::NORMAL_USER_EMAIL);

		//Ensure the logged in user is not currently registered for the test event
		if ($this->IsTestUserRegistered(self::TEST_EVENT_ID))
		{
			$this->DeregisterUserFromEvent(self::TEST_EVENT_ID);
		}

		//attempt to register a user to an event that does not exist
		$registerObj = [
			"event_id"  => self::BAD_EVENT_ID,
			"can_drive" => true,
			"visual"    => true,
			"mobility"  => true,
			"hearing"   => true
		];

		$this->client->request('POST', "/events/register", $registerObj);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_NOT_FOUND);

		//Attempt to register a user that is not currently registered to an event to the test event, attempt to register one for each of the four boolean values

		$driveValues = [true, false];
		$visualValues = [true, false];
		$mobility = [true, false];
		$hearing = [true, false];

		foreach ($driveValues as $drive)
		{
			foreach ($visualValues as $visual)
			{
				foreach ($mobility as $mobile)
				{
					foreach ($hearing as $deaf)
					{
						$registerObj = [
							"event_id"  => self::TEST_EVENT_ID,
							"can_drive" => $drive,
							"visual"    => $visual,
							"mobility"  => $mobile,
							"hearing"   => $deaf
						];

						//check if the event was registered ok
						$this->client->request('POST', "/events/register", $registerObj);
						$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
						$data = json_decode($this->lastResponse->getContent());

						$this->assertEquals(self::TEST_EVENT_ID, $data->event_id, "Event ID not found");
						$this->assertEquals(self::TEST_EVENT_NAME, $data->event_name, "Event name not found");

						$this->SetDatabaseConnection();
						$qb = $this->dbConn->createQueryBuilder();
						$qb->select('can_drive')
							->from('member')
							->where('user_id = :user_id')
							->setParameter('user_id', $this->GetLoggedInUserId());
						$result = $qb->execute()->fetchAll();
						$this->assertEquals($registerObj['can_drive'] ? 'true' : 'false', $result[0]['can_drive'], "can_drive not populated correctly for id " . $this->GetLoggedInUserId());
						$this->DeregisterUserFromEvent(self::TEST_EVENT_ID);
					}
				}
			}
		}

		//attempt to register a user to an event that they are already registered for
		$registerObj = [
			"event_id"  => self::TEST_EVENT_ID,
			"can_drive" => true,
			"visual"    => true,
			"mobility"  => true,
			"hearing"   => true
		];

		$this->client->request('POST', "/events/register", $registerObj);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_RESOURCE_CREATED);

		$this->client->request('POST', "/events/register", $registerObj);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_CONFLICT);

	}

	/**
	 * @group Event
	 */
	public function testDeregister()
	{
		$this->SetClient();
		$this->SetDatabaseConnection();

		$this->Login(clsTesterCreds::NORMAL_USER_EMAIL);

		if (!$this->IsTestUserRegistered(self::TEST_EVENT_ID))
		{
			$registerObj = [
				"event_id"  => self::TEST_EVENT_ID,
				"can_drive" => true,
				"visual"    => true,
				"mobility"  => true,
				"hearing"   => true
			];

			//check if the event was registered ok
			$this->client->request('POST', "/events/register", $registerObj);
			$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
		}

		$registerObj = [
			"event_id" => self::TEST_EVENT_ID
		];

		$this->client->request('POST', '/events/deregister', $registerObj);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_NO_CONTENT);

		//attempt to deregister a user when they are the captain of a team and the team has multiple people signed up

		$registerObj = [
			"event_id"  => self::TEST_EVENT_ID,
			"can_drive" => true,
			"visual"    => true,
			"mobility"  => true,
			"hearing"   => true
		];

		//Register with first user
		$this->client->request('POST', "/events/register", $registerObj);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
		$newCaptainId = $this->GetLoggedInUserId();
		//Register with second user
		$this->Login(clsTesterCreds::ORGANIZER_EMAIL);
		$this->client->request('POST', "/events/register", $registerObj);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_RESOURCE_CREATED);

		//set second user as captain
		$qb = $this->dbConn->createQueryBuilder();
		$qb->insert('team')
			->values([
				"event_id"        => self::TEST_EVENT_ID,
				"captain_user_id" => $this->GetLoggedInUserId(),
				"name"            => ":name"
			])
			->setParameter(':name', 'throwawayteam');

		$qb->execute();

		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
				'team_id',
				'captain_user_id'
			)
			->from('team')
			->where('event_id = :event_id')
			->andWhere('captain_user_id = :captain_id')
			->setParameter('event_id', self::TEST_EVENT_ID)
			->setParameter('captain_id', $this->GetLoggedInUserId());

		$teamId = $qb->execute()->fetch()['team_id'];

		$qb = $this->dbConn->createQueryBuilder();
		$qb->update('member')
			->set("team_id", $teamId)
			->where("user_id in ($newCaptainId, {$this->GetLoggedInUserId()})");

		$qb->execute();

		//Deregister the second user
		$deregisterObj = [
			"event_id" => self::TEST_EVENT_ID
		];

		$this->client->request('POST', '/events/deregister', $deregisterObj);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_NO_CONTENT);

		//check if a new captain is assigned
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('captain_user_id')
			->from('team')
			->where("team_id = $teamId");

		$testCaptainId = $qb->execute()->fetch()['captain_user_id'];
		$this->assertEquals($newCaptainId, $testCaptainId, "Failed to assign a new captain");

		//Deregister first user
		$this->Login(clsTesterCreds::NORMAL_USER_EMAIL);
		$this->DeregisterUserFromEvent(self::TEST_EVENT_ID);
	}

	private function IsTestUserRegistered($eventId)
	{
		if (!$this->GetLoggedIn())
		{
			return false;
		}

		//TODO: replace this interface call with a direct action from the database
		$this->client->request('GET', '/user/userInfo');
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);

		$content = json_decode($this->lastResponse->getContent());
		if (isset($content->event_id) && (int)$content->event_id === $eventId)
		{
			return true;
		}

		return false;
	}

	private function DeregisterUserFromEvent($eventId)
	{
		if (!$this->GetLoggedIn())
		{
			$this->assertTrue(false, "Unable to deregister user from event $eventId. Not logged in.");
		}

		//deregister the user
		$deregisterObj = [
			"event_id" => $eventId
		];

		//TODO: replace this interface call with a direct action from the database
		$this->client->request('POST', '/events/deregister', $deregisterObj);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_NO_CONTENT);
	}

}