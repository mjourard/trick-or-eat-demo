<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 1/23/2017
 * Time: 12:44 PM
 */

namespace TOETests\App\Controller;

use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsHTTPCodes;
use TOETests\BaseTestCase;
use TOETests\clsTestConstants;
use TOETests\clsTesterCreds;
use TOETests\clsTestHelpers;

class TeamControllerTest extends BaseTestCase
{
	const GOOD_EVENT_ID  = 1;
	const OTHER_EVENT_ID = 2;

	const APOSTROPHE_TEAM            = "My team's apostrophe!";
	const GOOD_TEAM_NAME_UNAVAILABLE = "Registered Name";

	const NON_EXISTENT_EVENT_ID = 999999;
	const BAD_EVENT_ID          = -1;
	const STRING_EVENT_ID       = "four";

	const BAD_TEAM_NAME = "\t \r\n";

	const DEFAULT_JOIN_CODE = '123';
	const WRONG_JOIN_CODE   = '000';

	public function testGetTeams()
	{
		$this->markTestIncomplete();
	}

	/**
	 * @group Team
	 */
	public function testJoinTeam()
	{
		/**
		 * case: a normal participant who is not registered for an event attempts to join a team
		 * expected: an error code is returned
		 */
		$this->InitializeTest(clsTesterCreds::NORMAL_USER_EMAIL);
		$this->client->request('POST', '/team/join', [
			'event_id'  => self::GOOD_EVENT_ID,
			'team_id'   => clsTestConstants::TEAM_OF_EMPTY_ID,
			'join_code' => self::DEFAULT_JOIN_CODE
		]);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);

		/**
		 * case: a normal participant who is registered for a different event attempts to join a team
		 * expected: an error code is returned
		 */
		$this->Login(clsTesterCreds::NORMAL_USER_REGISTERED_OTHER_EVENT_EMAIL);
		$this->client->request('POST', '/team/join', [
			'event_id'  => self::GOOD_EVENT_ID,
			'team_id'   => clsTestConstants::TEAM_OF_EMPTY_ID,
			'join_code' => self::DEFAULT_JOIN_CODE
		]);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);

		/**
		 * case: a normal participant who is registered for the event but is already on a team attempts to join a team
		 * expected: an error code is returned
		 */
		$this->Login(clsTesterCreds::NORMAL_USER_ON_TEAM_EMAIL);
		$this->client->request('POST', '/team/join', [
			'event_id'  => self::GOOD_EVENT_ID,
			'team_id'   => clsTestConstants::TEAM_OF_EMPTY_ID,
			'join_code' => self::DEFAULT_JOIN_CODE
		]);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);

		/**
		 * case: a normal participant who is registered for the event uses the wrong code while attempting to join a team
		 * expected: an error code is returned
		 */
		$this->Login(clsTesterCreds::NORMAL_USER_REGISTERED_EMAIL);
		$this->client->request('POST', '/team/join', [
			'event_id'  => self::GOOD_EVENT_ID,
			'team_id'   => clsTestConstants::TEAM_OF_EMPTY_ID,
			'join_code' => self::WRONG_JOIN_CODE
		]);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);

		/**
		 * case: a normal participant who is registered for the event and is not on a team attempts to join a team that is already full
		 * expected: an error code is returned
		 */
		$this->client->request('POST', '/team/join', [
			'event_id'  => self::GOOD_EVENT_ID,
			'team_id'   => clsTestConstants::TEAM_OF_FULL_ID,
			'join_code' => self::DEFAULT_JOIN_CODE
		]);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);

		/**
		 * case: a normal participant who is registered for an event and is not on a team attempts to join a team that does not have any placeholders on it
		 * expected: user joins the team
		 */

		/**
		 * case: a normal participant who is registered for an event and is not on a team attempts to join a team with available replacements and uses the correct code
		 * expected: user joins the team
		 */
		$this->client->request('POST', '/team/join', [
			'event_id'  => self::GOOD_EVENT_ID,
			'team_id'   => clsTestConstants::TEAM_OF_EMPTY_ID,
			'join_code' => self::DEFAULT_JOIN_CODE
		]);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
		$this->client->request('POST', '/team/leave');
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	/**
	 * @group Team
	 */
	public function testLeaveTeam()
	{
		$this->InitializeTest(clsTesterCreds::NORMAL_USER_EMAIL);

		/**
		 * case: a normal participant who is not on a team attempts to leave a team
		 * expected: no changes to the system
		 */
		$this->client->request('POST', '/team/leave');
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);

		/**
		 * case: a normal participant is captain and attempts to leave the team with 1+ real people on the team
		 * expected: the captain is removed and a non-placeholder teammate is made the captain
		 */
		//get the preteam
		$preteam = $this->getTeam(clsTesterCreds::NORMAL_USER_ON_TEAM_AS_CAPTAIN_EMAIL);
		$preCapId = $this->getCaptainIdFromTeammates($preteam->teammates);
		$this->assertEquals((int)$preCapId, $this->GetLoggedInUserId(), "Team didn't have the correct captain assigned");

		//leave
		$this->client->request('POST', '/team/leave');
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);

		//Get the post team
		$postTeam = $this->getTeam(clsTesterCreds::NORMAL_USER_ON_TEAM_EMAIL);
		$postCapId = $this->getCaptainIdFromTeammates($postTeam->teammates);

		$this->assertNotEquals($preCapId, $postCapId, "New captain was not assigned");

		//re-add them to the team
		$this->haveUserJoinTeam(clsTesterCreds::NORMAL_USER_ON_TEAM_AS_CAPTAIN_EMAIL, self::GOOD_EVENT_ID, clsTestConstants::PERMANENT_TEAM_ID, self::DEFAULT_JOIN_CODE);

		//make them the team captain again
		$capId = $this->GetLoggedInUserId();
		$qb = $this->dbConn->createQueryBuilder();
		$qb->update('team')
			->set('captain_user_id', $capId)
			->where('team_id = ' . clsTestConstants::PERMANENT_TEAM_ID);
		$this->assertEquals(1, $qb->execute(), "Did not reset the captain's user_id, database out of sync for testing.");

		/**
		 * case: a normal participant attempts to leave the team
		 * expected: the participant leaves the team, captain is the same
		 */
		$oldTeam = $this->getTeam(clsTesterCreds::NORMAL_USER_ON_TEAM_EMAIL);
		$oldTeammateId = $this->GetLoggedInUserId();
		$this->assertTrue($this->userIsOnTeam($oldTeammateId, $oldTeam->teammates), "User is not on the team.");
		$this->client->request('POST', '/team/leave');
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
		$newTeam = $this->getTeam(clsTesterCreds::NORMAL_USER_ON_TEAM_AS_CAPTAIN_EMAIL);
		$this->assertFalse($this->userIsOnTeam($oldTeammateId, $newTeam->teammates), "User was still on the team after leaving");
		$this->Login(clsTesterCreds::NORMAL_USER_ON_TEAM_EMAIL);
		$this->client->request('POST', '/team/join', [
			'event_id'  => self::GOOD_EVENT_ID,
			'team_id'   => clsTestConstants::PERMANENT_TEAM_ID,
			'join_code' => self::DEFAULT_JOIN_CODE
		]);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_RESOURCE_CREATED);

		/**
		 * case: a captain of a team of a one attempts to leave their team
		 * expected: The user leaves the team and the team is deleted.
		 */
		$this->Login(clsTesterCreds::NORMAL_USER_ON_TEAM_OF_ONE_AS_CAPTAIN_EMAIL);
		$this->client->request('POST', '/team/leave');
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);

		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('team_id')
			->from('team')
			->where('name = :name')
			->setParameter(':name', clsTestConstants::TEAM_OF_ONE_NAME, clsConstants::SILEX_PARAM_STRING);

		$team = $qb->execute()->fetchAll();
		$this->assertEmpty($team, "Didn't delete the now empty team.");

		$oldAI = clsTestHelpers::GetAutoIncrementValueOfTable($this->dbConn, 'team');
		$this->recreateDeletedTeam(clsTesterCreds::NORMAL_USER_ON_TEAM_OF_ONE_AS_CAPTAIN_EMAIL, clsTestConstants::TEAM_OF_ONE_NAME, 1, false, false, false, false, clsTestConstants::TEAM_OF_ONE_ID, $oldAI);

		/**
		 * case: A captain of an empty team attempts to leave their team
		 * expected: The user leaves and the team is deleted.
		 */
		$this->Login(clsTesterCreds::NORMAL_USER_ON_TEAM_OF_EMPTY_AS_CAPTAIN_EMAIL);
		$this->client->request('POST', '/team/leave');
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);

		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('team_id')
			->from('team')
			->where('name = :name')
			->setParameter(':name', clsTestConstants::TEAM_OF_EMPTY_NAME, clsConstants::SILEX_PARAM_STRING);

		$team = $qb->execute()->fetchAll();
		$this->assertEmpty($team, "Didn't delete the now empty team.");

		$oldAI = clsTestHelpers::GetAutoIncrementValueOfTable($this->dbConn, 'team');
		$this->recreateDeletedTeam(clsTesterCreds::NORMAL_USER_ON_TEAM_OF_EMPTY_AS_CAPTAIN_EMAIL, clsTestConstants::TEAM_OF_EMPTY_NAME, 2, false, false, false, false, clsTestConstants::TEAM_OF_EMPTY_ID, $oldAI);
	}

	/**
	 * @group Team
	 */
	public function testCreateTeam()
	{
		/**
		 * case: a normal participant who is registered for an event and is not on a team attempts to create a new team
		 * expected: the new team is created
		 */
		$this->InitializeTest(clsTesterCreds::NORMAL_USER_REGISTERED_EMAIL);
		$this->client->request('POST', '/team/create', [
			'Name'        => self::APOSTROPHE_TEAM,
			'memberCount' => clsConstants::MAX_ROUTE_MEMBERS,
			'join_code'   => self::DEFAULT_JOIN_CODE,
			"can_drive"   => true,
			"visual"      => true,
			"hearing"     => true,
			"mobility"    => true
		]);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
		$response = json_decode($this->lastResponse->getContent());
		$teamId = $response->team_id;
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			't.team_id',
			't.captain_user_id',
			't.name')
			->from('member', 'm')
			->leftJoin('m', 'team', 't', 'm.team_id = t.team_id')
			->where('m.user_id = ' . $this->GetLoggedInUserId())
			->orWhere('m.team_id = ' . $teamId)
			->orderBy('m.user_id', 'ASC');

		$results = $qb->execute()->fetchAll();
		$this->assertEquals(clsConstants::MAX_ROUTE_MEMBERS, count($results), 'Team wsa not created or the placeholder members were not created');
		$this->assertEquals($teamId, $results[0]['team_id'], 'The correct team was not returned');
		$this->assertEquals($this->GetLoggedInUserId(), $results[0]['captain_user_id'], 'User was not made captain when they created the team');

		//delete the placeholder users
		$this->deleteTempMembers($teamId);

		//delete the team
		$qb = $this->dbConn->createQueryBuilder();
		$qb->delete('team')
			->where('team_id = ' . $teamId);

		$qb->execute();

		/**
		 * case: a normal participant who is not registered for an event attempts to create a new team
		 * expected: an error code is returned
		 */
		$this->Login(clsTesterCreds::NORMAL_USER_EMAIL);
		$this->client->request('POST', '/team/create', [
			'Name'        => self::APOSTROPHE_TEAM,
			'memberCount' => clsConstants::MAX_ROUTE_MEMBERS,
			'join_code'   => self::DEFAULT_JOIN_CODE,
			"can_drive"   => true,
			"visual"      => true,
			"hearing"     => true,
			"mobility"    => true
		]);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_NOT_AUTHORIZED);

		/**
		 * case: a normal participant who is registered for an event and is on a team attempts to create a new team
		 * expected: an error code is returned
		 */
		$this->Login(clsTesterCreds::NORMAL_USER_ON_TEAM_EMAIL);
		$this->client->request('POST', '/team/create', [
			'Name'        => self::APOSTROPHE_TEAM,
			'memberCount' => clsConstants::MAX_ROUTE_MEMBERS,
			'join_code'   => self::DEFAULT_JOIN_CODE,
			"can_drive"   => true,
			"visual"      => true,
			"hearing"     => true,
			"mobility"    => true
		]);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_NOT_AUTHORIZED);

		/**
		 * case: a normal participant who is registered for an event and is not on a team attempts to create a new team with the same name as an existing team
		 * expected: an error code is returned
		 */
		$this->Login(clsTesterCreds::NORMAL_USER_REGISTERED_EMAIL);
		$this->client->request('POST', '/team/create', [
			'Name'        => clsTestConstants::PERMANENT_TEAM_NAME,
			'memberCount' => clsConstants::MAX_ROUTE_MEMBERS,
			'join_code'   => self::DEFAULT_JOIN_CODE,
			"can_drive"   => true,
			"visual"      => true,
			"hearing"     => true,
			"mobility"    => true
		]);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);

		/**
		 * case: a normal participant who is registered for an event and is not on a team attempts to create a new team with too many people on it
		 * expected: an error code is returned
		 */
		$this->Login(clsTesterCreds::NORMAL_USER_REGISTERED_EMAIL);
		$this->client->request('POST', '/team/create', [
			'Name'        => clsTestConstants::PERMANENT_TEAM_NAME,
			'memberCount' => clsConstants::MAX_ROUTE_MEMBERS + 1,
			'join_code'   => self::DEFAULT_JOIN_CODE,
			"can_drive"   => true,
			"visual"      => true,
			"hearing"     => true,
			"mobility"    => true
		]);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);

		/**
		 * case: a normal participant who is registered for an event and is not on a team attempts to create a new team with a bad join code.
		 * expected: an error code is returned
		 */
		$badCodes = [
			'12',
			'12a',
			'1234',
			'abc',
			"'''"
		];

		foreach ($badCodes as $code)
		{
			$this->Login(clsTesterCreds::NORMAL_USER_REGISTERED_EMAIL);
			$this->client->request('POST', '/team/create', [
				'Name'        => clsTestConstants::PERMANENT_TEAM_NAME,
				'memberCount' => clsConstants::MAX_ROUTE_MEMBERS + 1,
				'join_code'   => $code,
				"can_drive"   => true,
				"visual"      => true,
				"hearing"     => true,
				"mobility"    => true
			]);
			$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}
	}

	/**
	 * @group Team
	 */
	public function testKickTeammate()
	{
		$this->InitializeTest(clsTesterCreds::NORMAL_USER_EMAIL);

		/**
		 * case: a normal participant who is not on a team attempts to kick a random person on a team
		 * expected: no changes to the system
		 */
		$this->Login(clsTesterCreds::NORMAL_USER_ON_TEAM_EMAIL);
		$kickId = $this->GetLoggedInUserId();
		$this->Login(clsTesterCreds::NORMAL_USER_EMAIL);
		$this->client->request('POST', '/team/kick', [
			'teammate_id' => $kickId
		]);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		$team = $this->getTeam(clsTesterCreds::NORMAL_USER_ON_TEAM_EMAIL);
		$this->assertNotEmpty($team->teammates, "Team was not supposed to be empty");

		/**
		 * case: a normal participant is captain and attempts to kick a teammate
		 * expected: the teammate is removed from the team and is replaced by a placeholder teammate, maintaining the number of people on the team
		 */
		$this->Login(clsTesterCreds::NORMAL_USER_ON_TEAM_EMAIL);
		$kickId = $this->GetLoggedInUserId();
		$this->Login(clsTesterCreds::NORMAL_USER_ON_TEAM_AS_CAPTAIN_EMAIL);
		$this->client->request('POST', '/team/kick', [
			'teammate_id' => $kickId
		]);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);

		//re-add them to the team
		$this->Login(clsTesterCreds::NORMAL_USER_ON_TEAM_EMAIL);
		$this->client->request('POST', '/team/join', [
			'event_id'  => self::GOOD_EVENT_ID,
			'team_id'   => clsTestConstants::PERMANENT_TEAM_ID,
			'join_code' => self::DEFAULT_JOIN_CODE
		]);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_RESOURCE_CREATED);

		/**
		 * case: a normal participant attempts to kick a teammate
		 * expected: no changes in the system
		 */
		$this->Login(clsTesterCreds::NORMAL_USER_ON_TEAM_AS_CAPTAIN_EMAIL);
		$kickId = $this->GetLoggedInUserId();
		$this->Login(clsTesterCreds::NORMAL_USER_ON_TEAM_EMAIL);
		$this->client->request('POST', '/team/kick', [
			'teammate_id' => $kickId
		]);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_NOT_AUTHORIZED);

		/**
		 * case: a captain of a team attempts to kick a member of a different team
		 * expected: no changes in the system
		 */
		$this->Login(clsTesterCreds::NORMAL_USER_ON_TEAM_WITH_ROUTE_EMAIL);
		$kickId = $this->GetLoggedInUserId();
		$this->Login(clsTesterCreds::NORMAL_USER_ON_TEAM_AS_CAPTAIN_EMAIL);
		$this->client->request('POST', '/team/kick', [
			'teammate_id' => $kickId
		]);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);

		/**
		 * case: an admin and an organizer attempt to kick a teammate
		 * expected: the teammate is kicked
		 */
		$teammates = [
			clsTesterCreds::ADMIN_ON_TEAM_EMAIL,
			clsTesterCreds::ORGANIZER_ON_TEAM_EMAIL
		];
		$victim = clsTesterCreds::NORMAL_USER_ON_TEAM_EMAIL;
		$this->Login($victim);
		$kickId = $this->GetLoggedInUserId();
		foreach ($teammates as $email)
		{
			$this->Login($email);
			$this->client->request('POST', '/team/kick', [
				'teammate_id' => $kickId
			]);
			$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);

			//re-add them to the team
			$this->Login($victim);
			$this->client->request('POST', '/team/join', [
				'event_id' => self::GOOD_EVENT_ID,
				'team_id'  => clsTestConstants::PERMANENT_TEAM_ID,
				'join_code' => self::DEFAULT_JOIN_CODE
			]);
			$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
		}

		/**
		 * case: an admin and an organizer attempt to kick a member of a different team
		 * expected: no changes in the system
		 */
		$teammates = [
			clsTesterCreds::ADMIN_ON_TEAM_EMAIL,
			clsTesterCreds::ORGANIZER_ON_TEAM_EMAIL
		];
		$victim = clsTesterCreds::NORMAL_USER_ON_TEAM_WITH_ROUTE_EMAIL;
		$this->Login($victim);
		$kickId = $this->GetLoggedInUserId();
		foreach ($teammates as $email)
		{
			$this->Login($email);
			$this->client->request('POST', '/team/kick', [
				'teammate_id' => $kickId
			]);
			$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		/**
		 * case: A captain, admin and organizer all try to kick themselves.
		 * expected: no changes in the system
		 */
		$teammates = [
			clsTesterCreds::ADMIN_ON_TEAM_EMAIL,
			clsTesterCreds::ORGANIZER_ON_TEAM_EMAIL,
			clsTesterCreds::NORMAL_USER_ON_TEAM_AS_CAPTAIN_EMAIL
		];
		foreach ($teammates as $email)
		{
			$this->Login($email);
			$kickId = $this->GetLoggedInUserId();
			$this->client->request('POST', '/team/kick', [
				'teammate_id' => $kickId
			]);
			$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}
	}

	/**
	 * @group Team
	 */
	public function testIsTeamNameAvailable()
	{
		$this->SetClient();
		$this->SetDatabaseConnection();

		//test the command without being signed up for the event
		if ($this->GetLoggedIn())
		{
			$this->Signout();
		}
		$this->client->request('GET', $this->getTeamNameAvailabilityUrl(self::GOOD_EVENT_ID, clsTestConstants::PERMANENT_TEAM_NAME));
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_AUTH_REQUIRED);

		$this->Login(clsTesterCreds::NORMAL_USER_REGISTERED_EMAIL);

		//test sending it a blank team name
		$this->client->request('GET', $this->getTeamNameAvailabilityUrl(self::GOOD_EVENT_ID, ""));
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_NOT_FOUND);

		//test sending it a white space team name
		$this->client->request('GET', $this->getTeamNameAvailabilityUrl(self::GOOD_EVENT_ID, self::BAD_TEAM_NAME));
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_NOT_FOUND);

		//test sending it a team name with an apostrophe in it
		$this->client->request('GET', $this->getTeamNameAvailabilityUrl(self::GOOD_EVENT_ID, self::APOSTROPHE_TEAM));
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
		$this->assertTrue(json_decode($this->lastResponse->getContent())->available, "Name was deemed unavailable");

		//test sending it a team name that is already registered for a different event
		$this->client->request('GET', $this->getTeamNameAvailabilityUrl(self::OTHER_EVENT_ID, clsTestConstants::OTHER_PERMANENT_TEAM_NAME));
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
		$this->assertTrue(json_decode($this->lastResponse->getContent())->available, "Name was deemed unavailable when it should have been available");

		//test sending it a team name that is already registered for that event
		$this->client->request('GET', $this->getTeamNameAvailabilityUrl(self::GOOD_EVENT_ID, clsTestConstants::PERMANENT_TEAM_NAME));
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
		$this->assertFalse(json_decode($this->lastResponse->getContent())->available, "Name was deemed available when it should have been unavailable");
	}

	//TODO: delete this function as it seems no longer necessary
	private function getTeamNameAvailabilityUrl($id, $name)
	{
		return "/team/exists/" . rawurlencode($name);
	}

	/**
	 * Gets the team info of the user of the email passed in. Changes who is logged in to the email passed in
	 *
	 * @param $email
	 */
	private function getTeam($email)
	{
		$this->Login($email);
		$this->client->request('GET', '/team/team');
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);

		return json_decode($this->lastResponse->getContent())->team;
	}

	/**
	 * Gets the captain's user_id from a list of teammates
	 *
	 * @param $teammates
	 *
	 * @return bool
	 */
	private function getCaptainIdFromTeammates($teammates)
	{
		$preCapId = false;
		foreach ($teammates as $teammate)
		{
			if ($teammate->is_captain === true)
			{
				$preCapId = $teammate->user_id;
			}
		}

		return $preCapId;
	}

	private function haveUserJoinTeam($email, $eventId, $teamId, $joinCode)
	{
		$this->Login($email);
		$this->client->request('POST', '/team/join', [
			'event_id'  => $eventId,
			'team_id'   => $teamId,
			'join_code' => $joinCode
		]);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_RESOURCE_CREATED);

	}

	/**
	 * Loops through teammates looking for the passed in $userId
	 *
	 * @param       $userId
	 * @param array $teammates
	 *
	 * @param bool  $debug If true, var_dumps $teammates
	 *
	 * @return bool
	 */
	private function userIsOnTeam($userId, $teammates, $debug = false)
	{
		if ($debug === true)
		{
			var_dump($teammates);
		}

		$ret = false;
		foreach ($teammates as $teammate)
		{
			if ($teammate->user_id == $userId)
			{
				$ret = true;
			}
		}

		return $ret;
	}

	/**
	 * Recreates a deleted team, resetting it's team_id to what's passed in as well as resetting the auto-increment value.
	 *
	 * @param string $email
	 * @param string $teamName
	 * @param int    $memberCount
	 * @param bool   $canDrive
	 * @param bool   $visual
	 * @param bool   $hearing
	 * @param bool   $mobility
	 * @param int    $oldTeamId
	 * @param int    $oldTeamAutoIncrement
	 */
	private function recreateDeletedTeam($email, $teamName, $memberCount, $canDrive, $visual, $hearing, $mobility, $oldTeamId, $oldTeamAutoIncrement = null)
	{
		//create the team
		$this->Login($email);
		$this->client->request('POST', '/team/create', [
			'Name'        => $teamName,
			'memberCount' => $memberCount,
			'join_code'   => self::DEFAULT_JOIN_CODE,
			"can_drive"   => $canDrive,
			"visual"      => $visual,
			"hearing"     => $hearing,
			"mobility"    => $mobility
		]);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
		$newTeamId = json_decode($this->lastResponse->getContent())->team_id;

		//change the teammate's team_ids to null
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			'user_id'
		)
			->from('member')
			->where('team_id = ' . $newTeamId);

		$teammates = $qb->execute()->fetchAll();
		foreach ($teammates as &$row)
		{
			$row = $row['user_id'];
		}

		$qb = $this->dbConn->createQueryBuilder();
		$qb->update('member')
			->set('team_id', 'null')
			->where('user_id in (' . implode(",", $teammates) . ')');
		$qb->execute();

		$qb = $this->dbConn->createQueryBuilder();
		$qb->update('team')
			->set('team_id', $oldTeamId)
			->where("team_id = $newTeamId");
		$qb->execute();

		$qb = $this->dbConn->createQueryBuilder();
		$qb->update('member')
			->set('team_id', $oldTeamId)
			->where('user_id in (' . implode(",", $teammates) . ')');
		$qb->execute();

		if ($oldTeamAutoIncrement !== null)
		{
			$query = "
				ALTER TABLE team
				AUTO_INCREMENT = $oldTeamAutoIncrement
				";

			$prepped = $this->dbConn->prepare($query);
			$prepped->execute();
		}
	}

	private function deleteTempMembers($teamId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->delete('user')
			->where("email LIKE :email")
			->setParameter(':email', $teamId . "_%@" . clsConstants::PLACEHOLDER_EMAIL, clsConstants::SILEX_PARAM_STRING);

		return $qb->execute();
	}

}