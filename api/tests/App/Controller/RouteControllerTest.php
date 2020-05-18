<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 11/23/2016
 * Time: 2:51 AM
 */

namespace TOETests\App\Controller;

use League\OAuth2\Client\Grant\AbstractGrant;
use TOE\App\Service\Bus\BusManager;
use TOE\App\Service\Event\EventManager;
use TOE\App\Service\Event\RegistrationManager;
use TOE\App\Service\Route\Archive\Route;
use TOE\App\Service\Route\Archive\RouteManager;
use TOE\App\Service\Route\Assignment\AssignmentManager;
use TOE\App\Service\Team\TeamManager;
use TOE\App\Service\User\NewUser;
use TOE\App\Service\User\UserLookupService;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\HTTPCodes;
use TOETests\BaseTestCase;
use TOETests\clsTestConstants;
use TOETests\clsTesterCreds;

class RouteControllerTest extends BaseTestCase
{
	public const GOOD_EVENT_ID = 1;
	public const BAD_EVENT_ID = -1;

	public const TEMP_EMAIL_USERNAME = "routeControllerTestEmail";
	public const TEMP_TEAM_NAME = "routetestingteam";

	public const TEMP_ROUTE_NAME = "2-AB-route-2-modded.kmz";
	public const TEMP_ROUTE_FILE_URL = "http://localhost/route-files/2-59db0030f23b7.kmz";
	public const TEMP_ROUTE_TYPE = "Bus";
	public const TEMP_BUS_NAME = "";
	public const TEMP_ROUTE_ID = 1;

	public const ALLOCATE_OBJECT_FILE = "/routes/allocate.json";
	public const ALLOCATE_DATA = [
		"zoneId"  => [],
		"routeId" => [],
		"eventId" => []
	];

	/**
	 * @group Route-new
	 */
	public function testAllocateRoute()
	{
		$this->login(clsTesterCreds::NORMAL_USER_EMAIL);
		$this->client->request('POST', '/routes/allocate', [
			'zoneId'  => 1,
			'routeId' => 1,
			'eventId' => 1
		]);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_AUTHORIZED);

		$this->login(clsTesterCreds::ORGANIZER_EMAIL);

		//test with a route that doesn't exist
		$this->client->request('POST', '/routes/allocate', [
			'zoneId'  => 9999,
			'routeId' => 1,
			'eventId' => 1
		]);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_FOUND);

		//test with an event that doesn't exist
		$this->client->request('POST', '/routes/allocate', [
			'zoneId'  => 1,
			'routeId' => 9999,
			'eventId' => 1
		]);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_FOUND);

		$this->client->request('POST', '/routes/allocate', [
			'zoneId'  => 1,
			'routeId' => 1,
			'eventId' => 9999
		]);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_FOUND);

		//test with a route that is already allocated
		$this->client->request('POST', '/routes/allocate', [
			'zoneId'  => 2,
			'routeId' => 1,
			'eventId' => 1
		]);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_BAD_REQUEST);

		//test with a route that is allocated
		/** @var RouteManager $routeManager */
		$routeManager = $this->app['route.manager'];
		$route = $routeManager->saveRouteInfo(new Route([
			'route_file_url'        => 'image.jpg',
			'route_name'            => __FUNCTION__ . "-test",
			'required_people'       => '5',
			'type'                  => AssignmentManager::ROUTE_TYPE_WALK,
			'wheelchair_accessible' => 'true',
			'blind_accessible'      => 'true',
			'hearing_accessible'    => 'true',
			'zone_id'               => 1,
			'owner_user_id'         => $this->getLoggedInUserId()
		]));
		$this->client->request('POST', '/routes/allocate', [
			'zoneId'  => 1,
			'routeId' => $route->getRouteId(),
			'eventId' => 1
		]);
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);
		$content = json_decode($this->lastResponse->getContent());
		self::assertNotNull($content);
		self::assertTrue($content->success);
		self::assertNotEmpty($content->route_allocation_id);
		$this->setDatabaseConnection();
		$this->dbConn->createQueryBuilder()
			->delete('route_allocation')
			->where('route_allocation_id = ' . $content->route_allocation_id)
			->execute();

		$this->dbConn->createQueryBuilder()
			->delete('route_archive')
			->where('route_id = ' . $route->getRouteId())
			->execute();
	}

	/**
	 * @group Route-new
	 */
	public function testDeallocateRoute()
	{
		self::markTestIncomplete();
	}

	/**
	 * @group Route
	 */
	public function testGetRouteAssignments()
	{
		//test attempting to get all route assignments as regular user
		$this->login(clsTesterCreds::NORMAL_USER_EMAIL);

		$this->client->request('GET', $this->getRouteAssignmentsUrl(self::GOOD_EVENT_ID, 'route_id'));
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_AUTHORIZED);

		//test sending a bad event_id
		$this->loginAsAdmin();

		$this->client->request('GET', $this->getRouteAssignmentsUrl(self::BAD_EVENT_ID, 'route_id'));
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_FOUND);

		/** @var EventManager $eventManager */
		$eventManager = $this->app['event'];
		$eventId = $eventManager->createNewEvent(EventControllerTest::REGION_ID_WITH_EVENT, __FUNCTION__ . "-emptyevent", new \DateTime('now', new \DateTimeZone('utc')));
		self::assertNotEmpty($eventId, "Unable to create new event");

		$this->client->request('GET', $this->getRouteAssignmentsUrl($eventId, 'route_id'));
		//test getting back data from an event with no teams signed up yet
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

		//cleanup the new event
		$this->setDatabaseConnection();
		$this->dbConn->createQueryBuilder()
			->delete('event')
			->where("event_id = $eventId");

		//test getting back data with multiple teams signed up
		//assign some teams
		/** @var UserLookupService $userLookup */
		$userLookup = $this->app['user.lookup'];

		//create the users to be inserted


		//TODO: add some names that have unicode characters in them.
		//TODO: increase the number of teams signed up to something like 1000. Find an upper limit
		$newUsers = [];
		for($i = 0; $i < 9; $i++)
		{
			$newUsers[] = new NewUser(
				self::TEMP_EMAIL_USERNAME . "$i@test.com",
				'password',
				"pion$i",
				'disposable',
				9,
				Constants::ROLE_PARTICIPANT,
				true,
				true,
				true
			);
		}
		$users = $userLookup->registerUsers($newUsers);

		//register the users for the event
		/** @var RegistrationManager $regManager */
		$regManager = $this->app['event.registration'];
		foreach($users as $userId)
		{
			$regManager->registerForEvent($userId, self::GOOD_EVENT_ID, false);
		}

		//create the routes to insert
		/** @var RouteManager $routeManager */
		$routeManager = $this->app['route.manager'];
		$routeIds = [];
		for($i = 0; $i < 2; $i++)
		{
			$route = new Route([
				'route_file_url'        => 'image.jpg',
				'route_name'            => "route$i",
				'required_people'       => '5',
				'type'                  => AssignmentManager::ROUTE_TYPE_WALK,
				'wheelchair_accessible' => 'true',
				'blind_accessible'      => 'true',
				'hearing_accessible'    => 'true',
				'zone_id'               => 1,
				'owner_user_id'         => $this->getLoggedInUserId(),
			]);
			$route = $routeManager->saveRouteInfo($route);
			self::assertNotEmpty($route->getRouteId(), "Unable to save route info for route $i");
			$routeIds[] = $route->getRouteId();
		}

		//create the buses used for routes
		/** @var BusManager $busManager */
		$busManager = $this->app['bus'];
		$busIds = [];
		$now = new \DateTime('now', new \DateTimeZone('utc'));
		$later = clone $now;
		$later->add(new \DateInterval('PT3H'));
		for($i = 0; $i < 2; $i++)
		{
			$busId = $busManager->addBus("bus$i", $now, $later, 1);
			self::assertNotEmpty($busId, "Unable to create bus in database");
			$busIds[] = $busId;
		}

		//create the active routes
		/** @var AssignmentManager $assignmentManager */
		$assignmentManager = $this->app['route.assignment'];
		$routeAllocationIds = [];
		for($i = 0; $i < count($routeIds); $i++)
		{
			$id = $assignmentManager->allocateRouteToEvent($routeIds[$i], self::GOOD_EVENT_ID);
			self::assertNotEmpty($id, "No route allocation id produced on route $i");
			$routeAllocationIds[] = $id;
		}
		foreach($routeAllocationIds as $idx => $routeAllocationId)
		{
			self::assertTrue($assignmentManager->assignBusToRouteAllocation($routeAllocationId, $busIds[$idx]));
		}

		//create the teams
		/** @var TeamManager $teamManager */
		$teamManager = $this->app['team'];
		$captainIds = [$users[0], $users[5], $users[8]];

		$teamIds = [];

		$teamIds[] = $teamManager->createTeam($users[0], self::GOOD_EVENT_ID, self::TEMP_TEAM_NAME . "0", '123', false, false, false, false, 5);
		$teamIds[] = $teamManager->createTeam($users[5], self::GOOD_EVENT_ID, self::TEMP_TEAM_NAME . "1", '123', false, false, false, false, 3);
		$teamIds[] = $teamManager->createTeam($users[8], self::GOOD_EVENT_ID, self::TEMP_TEAM_NAME . "2", '123', false, false, false, false, 1);


		//assign the routes to the teams
		foreach($routeAllocationIds as $idx => $routeAllocationId)
		{
			self::assertTrue($assignmentManager->assignRouteToTeam($teamIds[$idx], $routeAllocationId), "Unable to assign team {$teamIds[$idx]} (idx $idx) with route allocation $routeAllocationId");
		}


		//FINALLY, get route assignments and make sure all the data is as expected
		$this->client->request('GET', $this->getRouteAssignmentsUrl(self::GOOD_EVENT_ID, 'route_id'));

		//clean up the database before making a bunch of asserts...
		$this->dbConn->createQueryBuilder()
			->delete('team_route')
			->where('team_id in (' . implode(',', $teamIds) . ')')
			->execute();

		$this->dbConn->createQueryBuilder()
			->delete('team')
			->where('team_id in (' . implode(',', $teamIds) . ')')
			->execute();

		$this->dbConn->createQueryBuilder()
			->delete('route_allocation')
			->where('route_id in (' . implode(',', $routeIds) . ')')
			->execute();

		$qb = $this->dbConn->createQueryBuilder();
		$qb->delete('bus')
			->where($qb->expr()->in('bus_id', $busIds));

		$qb->execute();

		$qb->delete('route_archive')
			->where('route_id in (' . implode(',', $routeIds) . ')');

		$qb->execute();

		$qb->delete('member')
			->where('user_id in (' . implode(',', $users) . ')');

		$qb->execute();

		$qb->delete('user')
			->where('user_id in (' . implode(',', $users) . ')');

		$qb->execute();

		//assert the data was correct
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);

		$content = json_decode($this->lastResponse->getContent());
		self::assertNotNull($content);
		self::assertTrue($content->success);
		self::assertNotEmpty($content->routes);
		self::assertNotEmpty($content->unassignedTeams, "Unassigned teams contains: " . print_r($content->unassignedTeams, true));
		self::assertEquals(3, $content->stats->totalRoutes);
		self::assertEquals(0, $content->stats->fullRoutes);
		self::assertEquals(0, $content->stats->emptyRoutes);
		self::assertEquals(8, $content->stats->teamCount);
		self::assertEquals(5, $content->stats->unassignedTeams);
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

	protected function getRouteAssignmentsUrl(int $eventId, $orderByColumn, bool $orderIsAsc = true)
	{
		if(!$orderIsAsc)
		{
			$orderByColumn = "-" . $orderByColumn;
		}

		return '/routes/' . $eventId . '/getRouteAssignments/orderBy/' . $orderByColumn;
	}

}