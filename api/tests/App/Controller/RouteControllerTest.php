<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 11/23/2016
 * Time: 2:51 AM
 */

namespace TOETests\App\Controller;

use TOE\GlobalCode\Constants;
use TOE\GlobalCode\HTTPCodes;
use TOETests\BaseTestCase;
use TOETests\clsTestConstants;
use TOETests\clsTesterCreds;

class RouteControllerTest extends BaseTestCase
{
	public  const GOOD_EVENT_ID = 1;
	public  const BAD_EVENT_ID  = -1;

	public  const TEMP_EMAIL_USERNAME = "routeControllerTestEmail";
	public  const TEMP_TEAM_NAME      = "routetestingteam";

	public  const TEMP_ROUTE_NAME     = "2-AB-route-2-modded.kmz";
	public  const TEMP_ROUTE_FILE_URL = "/2-59db0030f23b7.kmz";
	public  const TEMP_ROUTE_TYPE     = "Bus";
	public  const TEMP_BUS_NAME       = "";
	public  const TEMP_ROUTE_ID       = 1;

	public  const ALLOCATE_OBJECT_FILE = "/routes/allocate.json";
	public  const ALLOCATE_DATA        = [
		"zoneId"  => [],
		"routeId" => [],
		"eventId" => []
	];

	/**
	 * @group Route-new
	 */
	public function testAllocateRoute()
	{
		//test with a route that doesn't exist
		self::markTestIncomplete();
//		$this->LoadJSONObject(clsTestConstants::TEST_DATA_FOLDER_PATH . self::ALLOCATE_OBJECT_FILE);

		//test with an event that doesn't exist

		//test with an event that is not allocated

		//test with an event that is already allocated
	}

	/**
	 * @group Route-new
	 */
	public function testDeallocateRoute()
	{
		self::markTestIncomplete();
	}

	/**
	 * @group Route-broken
	 */
	public function testGetRouteAssignments()
	{
		//self::markTestIncomplete();
		//test attempting to get all route assignments as regular user
		$this->login(clsTesterCreds::NORMAL_USER_EMAIL);

		$this->client->request('GET', '/routes/' . self::GOOD_EVENT_ID . '/getRouteAssignments');
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_AUTHORIZED);

		//test sending a bad event_id
		$this->loginAsAdmin();

		$this->client->request('GET', '/routes/' . self::BAD_EVENT_ID . '/getRouteAssignments');
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_FOUND);

		//test getting back data from an event with no teams signed up yet
		$this->client->request('GET', '/routes/' . self::GOOD_EVENT_ID . '/getRouteAssignments');
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);

		$content = json_decode($this->lastResponse->getContent());
		self::assertNotNull($content);
		self::assertTrue($content->success);
		self::assertEmpty($content->routes);
		self::assertEmpty($content->unassignedTeams);
		self::assertEquals(0, $content->stats->totalRoutes);
		self::assertEquals(0, $content->stats->fullRoutes);
		self::assertEquals(0, $content->stats->emptyRoutes);
		self::assertEquals(0, $content->stats->teamCount);
		self::assertEquals(0, $content->stats->unassignedTeams);

		//test getting back data with multiple teams signed up

		//assign some teams

		//create the users to be inserted
		$this->setDatabaseConnection();
		$q = "
			INSERT INTO USER
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
			VALUES";

		//TODO: add some names that have unicode characters in them.
		//TODO: increase the number of teams signed up to something like 1000. Find an upper limit
		for ($i = 0; $i < 9; $i++)
		{
			$q .= "('" . self::TEMP_EMAIL_USERNAME . "$i@test.com" . '\',\'$2y$10$SZ7H6yhS4JGTWWY6SskuxO4dyG6R3c5is2GVDJWvIIQEGaKPM4/X.\'' . ",'pion$i','disposable',NOW(),9,true,true,true),";
		}
		$q = rtrim($q, ",");

		$query = $this->dbConn->prepare($q);
		self::assertTrue($query->execute());

		//register the users for the event
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('user_id')
			->from('user')
			->where("email like :email")
			->setParameter(':email', self::TEMP_EMAIL_USERNAME . "%");

		$users = $qb->execute()->fetchAll();

		$q = "
			INSERT INTO USER_ROLE
			(
				user_id,
				role
			)
			VALUES
		";
		foreach ($users as $row)
		{
			$q .= "({$row['user_id']}, '" . Constants::ROLE_PARTICIPANT . "'),";
		}
		$q = rtrim($q, ",");

		$query = $this->dbConn->prepare($q);
		self::assertTrue($query->execute());

		$q = "
			INSERT INTO MEMBER
			(
				user_id,
				event_id,
				can_drive
			)
			VALUES
		";
		foreach ($users as $row)
		{
			$q .= "({$row['user_id']}, " . self::GOOD_EVENT_ID . ", 'false'),";
		}
		$q = rtrim($q, ",");

		$query = $this->dbConn->prepare($q);
		self::assertTrue($query->execute());

		//create the routes to insert
		$q = "
			INSERT INTO ROUTE_ARCHIVE
			(
			 	route_file_url,
			 	route_name,
			 	required_people,
			 	type,
			 	wheelchair_accessible,
			 	blind_accessible,
			 	hearing_accessible,
			 	zone_id,
			 	owner_user_id
			)
			VALUES
		";

		for ($i = 0; $i < 2; $i++)
		{
			$q .= "('image.jpg','route$i', 5, 'Walk', true, true, true, 1, {$this->getLoggedInUserId()}),";
		}
		$q = rtrim($q, ",");
		$query = $this->dbConn->prepare($q);
		self::assertTrue($query->execute());

		//create the buses used for routes
		$q = "
			INSERT INTO BUS
			(
				bus_name,
				start_time,
				end_time,
				zone_id
			)
			VALUES
		";

		for ($i = 0; $i < 2; $i++)
		{
			$q .= "('bus$i',NOW(), NOW(), 1),";
		}
		$q = rtrim($q, ",");
		$query = $this->dbConn->prepare($q);
		self::assertTrue($query->execute());

		//create the active routes
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('bus_id')
			->from('bus');

		$results = $qb->execute()->fetchAll();
		$busIds = [];
		foreach ($results as $row)
		{
			$busIds[] = $row['bus_id'];
		}

		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('route_id')
			->from('route_archive');

		$results = $qb->execute()->fetchAll();
		$routeIds = [];
		foreach ($results as $row)
		{
			$routeIds[] = $row['route_id'];
		}

		self::assertEquals(count($busIds), count($routeIds), "Different number of buses vs routes in the database. Should be the same, check integrity of test database.");

		$q = "
			INSERT INTO ROUTE
			(
				route_id,
				event_id,
				start_time,
				bus_id
			)
			VALUES
			";

		for ($i = 0; $i < count($busIds); $i++)
		{
			$q .= "({$routeIds[$i]}, " . self::GOOD_EVENT_ID . ", NOW(), {$busIds[$i]}),";
		}
		$q = rtrim($q, ",");
		$query = $this->dbConn->prepare($q);
		self::assertTrue($query->execute());

		//create the teams
		$q = "
			INSERT INTO TEAM
			(
				event_id,
				route_id,
				captain_user_id,
				name
			)
			VALUES
			";

		$captainIds = [$users[0]['user_id'], $users[5]['user_id'], $users[8]['user_id']];

		for ($i = 0; $i < count($routeIds); $i++)
		{
			$q .= "(" . self::GOOD_EVENT_ID . ", {$routeIds[$i]}, {$captainIds[$i]}, '" . self::TEMP_TEAM_NAME . "$i'),";
		}

		$q .= "(" . self::GOOD_EVENT_ID . ", NULL, {$captainIds[count($routeIds)]}, '" . self::TEMP_TEAM_NAME . "-noroute')";
		$query = $this->dbConn->prepare($q);
		self::assertTrue($query->execute());

		//assign the members to the new teams

		//get the new team ids
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('team_id')
			->from('team')
			->where('name like :name')
			->orderBy('team_id', 'asc')
			->setParameter('name', self::TEMP_TEAM_NAME . '%', Constants::SILEX_PARAM_STRING);

		$results = $qb->execute()->fetchAll();
		$teamIds = [];
		foreach ($results as $row)
		{
			$teamIds[] = $row['team_id'];
		}

		//create the 'full' team
		$teamUserIds = [];
		for ($i = 0; $i < 5; $i++)
		{
			$teamUserIds[] = $users[$i]['user_id'];
		}

		$qb = $this->dbConn->createQueryBuilder();
		$qb->update('member')
			->set('team_id', $teamIds[0])
			->where("user_id in (" . implode(',', $teamUserIds) . ")");

		$qb->execute();

		//create the 'partial' team
		$teamUserIds = [];
		for ($i = 5; $i < 8; $i++)
		{
			$teamUserIds[] = $users[$i]['user_id'];
		}

		$qb = $this->dbConn->createQueryBuilder();
		$qb->update('member')
			->set('team_id', $teamIds[1])
			->where("user_id in (" . implode(',', $teamUserIds) . ")");

		$qb->execute();

		//this team won't get assigned a route
		$qb = $this->dbConn->createQueryBuilder();
		$qb->update('member')
			->set('team_id', $teamIds[2])
			->where("user_id = {$users[8]['user_id']}");

		$qb->execute();

		//FINALLY, get route assignments and make sure all the data is as expected
		$this->client->request('GET', '/routes/' . self::GOOD_EVENT_ID . '/getRouteAssignments');

		//clean up the database before making a bunch of asserts...
		$qb = $this->dbConn->createQueryBuilder();
		$qb->delete('team')
			->where('team_id in (' . implode(',', $teamIds) . ')');

		$qb->execute();

		$qb->delete('route')
			->where('route_id in (' . implode(',', $routeIds) . ')');

		$qb->execute();

		$qb->delete('bus')
			->where('bus_id > 0');

		$qb->execute();

		$qb->delete('route_archive')
			->where('route_id in (' . implode(',', $routeIds) . ')');

		$qb->execute();

		$userIds = [];
		foreach ($users as $row)
		{
			$userIds[] = $row['user_id'];
		}

		$qb->delete('member')
			->where('user_id in (' . implode(',', $userIds) . ')');

		$qb->execute();

		$qb->delete('user')
			->where('user_id in (' . implode(',', $userIds) . ')');

		$qb->execute();

		//assert the data was correct
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);

		$content = json_decode($this->lastResponse->getContent());
		self::assertNotNull($content);
		self::assertTrue($content->success);
		self::assertNotEmpty($content->routes);
		self::assertNotEmpty($content->unassignedTeams, "Unassigned teams contains: " . print_r($content->unassignedTeams, true));
		self::assertEquals(2, $content->stats->totalRoutes);
		self::assertEquals(1, $content->stats->fullRoutes);
		self::assertEquals(0, $content->stats->emptyRoutes);
		self::assertEquals(3, $content->stats->teamCount);
		self::assertEquals(1, $content->stats->unassignedTeams);

	}

	/**
	 * @group Route
	 */
	public function testGetRouteAssignmentsForTeam()
	{
		$this->setClient();
		//test with no login
		$this->client->request('GET', '/routes/' . self::GOOD_EVENT_ID . '/getRouteAssignments/1');
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_AUTH_REQUIRED);

		$this->setDatabaseConnection();

		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('team_id')
			->from('team')
			->where('name = :name')
			->setParameter('name', clsTestConstants::PERMANENT_TEAM_NAME, Constants::SILEX_PARAM_STRING);

		$results = $qb->execute()->fetch();
		$teamId = $results['team_id'];

		//test getting team data back with user not assigned to the team
		$this->login(clsTesterCreds::NORMAL_USER_REGISTERED_EMAIL);
		$this->client->request('GET', '/routes/' . self::GOOD_EVENT_ID . "/getRouteAssignments/$teamId");
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_AUTHORIZED);

		//test getting route assignments for a team with a bad event_id
		$this->client->request('GET', '/routes/' . self::BAD_EVENT_ID . "/getRouteAssignments/$teamId");
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_FOUND);

		//test getting route assignment data for a team that has not yet been assigned a route
		$this->login(clsTesterCreds::ADMIN_ON_TEAM_EMAIL);
		$this->client->request('GET', '/routes/' . self::GOOD_EVENT_ID . "/getRouteAssignments/$teamId");
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);

		$routes = json_decode($this->lastResponse->getContent());
		self::assertEmpty($routes->routes, "Did not return zero routes.");

		//test getting route assignment data for a team that has been assigned a route
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('team_id')
			->from('team')
			->where('name = :name')
			->setParameter('name', clsTestConstants::PERMANENT_TEAM_NAME_WITH_ROUTE, Constants::SILEX_PARAM_STRING);

		$results = $qb->execute()->fetch();
		$teamId = $results['team_id'];


		$this->login(clsTesterCreds::NORMAL_USER_ON_TEAM_WITH_ROUTE_EMAIL);
		$this->client->request('GET', '/routes/' . self::GOOD_EVENT_ID . "/getRouteAssignments/$teamId");
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);

		$content = json_decode($this->lastResponse->getContent());

		self::assertNotNull($content);
		self::assertNotEmpty($content->routes);
		self::assertEquals(self::TEMP_BUS_NAME, $content->routes[0]->bus_name, "Bus name was not expected");
		self::assertEquals(self::TEMP_ROUTE_TYPE, $content->routes[0]->type, "route type was not expected");
		self::assertEquals(self::TEMP_ROUTE_NAME, $content->routes[0]->route_name, "route name was not expected");
		self::assertEquals(self::TEMP_ROUTE_FILE_URL, $content->routes[0]->route_file_url, "route image was not expected");

	}

	/**
	 * @group Route-new
	 */
	public function testGetRoutesForEvent()
	{
		self::markTestIncomplete();
	}

	/**
	 * @group Route-new
	 */
	public function testGetUnallocatedRoutes()
	{
		self::markTestIncomplete();
	}

	/**
	 * @group Route
	 */
	public function testAssignAllRoutes()
	{
		//TODO: this entire test
		self::markTestIncomplete();
		//test with basic user login

		//test with an event_id that doesn't exist

		//a bunch of affirmation test cases...
	}

	/**
	 * @group Route
	 */
	public function testRemoveAllRouteAssignments()
	{
		self::markTestIncomplete();
		//test with basic user login
		$this->login(clsTesterCreds::NORMAL_USER_EMAIL);
		$this->client->request('PUT', '/routes/' . self::GOOD_EVENT_ID . '/removeAllRouteAssignments');
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_AUTHORIZED);

		//test with no routes assigned
		//TODO: fill in these test cases as well
		self::markTestIncomplete();

		//test with some routes assigned
	}
}