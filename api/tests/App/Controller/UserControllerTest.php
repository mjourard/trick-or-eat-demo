<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 1/13/2017
 * Time: 9:41 AM
 */
namespace TOETests\App\Controller;

use TOE\GlobalCode\HTTPCodes;
use TOETests\BaseTestCase;
use TOETests\clsTesterCreds;


/**
 * Class UserControllerTest
 * The following parameters get passed to each function in the $app['params'] array:
 *
 * 'user/update' => [
 *     'first_name' => constants::SILEX_PARAM_STRING,
 *     'last_name' => constants::SILEX_PARAM_STRING,
 *     'region_id' => constants::SILEX_PARAM_INT
 *  ]
 */
class UserControllerTest extends BaseTestCase
{
	public const CUR_FIRST_NAME = "normal";
	public const CUR_LAST_NAME  = "user";
	public const CUR_REGION_ID  = 9;

	public const NEW_FIRST_NAME = "abnormal";
	public const NEW_LAST_NAME  = "non-user";
	public const NEW_REGION_ID  = 1;

	public const BAD_REGION_ID = 10000000;

	/**
	 * @group User
	 */
	public function testGetUserInfo()
	{
		$this->setClient();
		//try without any creds
		if ($this->getLoggedIn())
		{
			$this->signout();
		}

		$this->client->request('GET', '/user/userInfo');
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_AUTH_REQUIRED);

		//confirm all data returned is the correct data
		$this->login(clsTesterCreds::NORMAL_USER_EMAIL);
		$this->client->request('GET', '/user/userInfo');
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);

		$expected = [
			'user_roles'   => ['participant'],
			'first_name'   => 'user',
			'last_name'    => 'notreg',
			'region_id'    => 9,
			'event_id'     => null,
			'event_name'   => null,
			'team_id'      => null,
			'team_name'    => null,
			'region_name'  => "Ontario",
			'country_id'   => 1,
			'country_name' => "Canada",
			'email'        => "normaluser@toetests.com",
			'id'           => 2,
			'success'      => true,
			'message'      => ''

		];

		$responseData = json_decode($this->lastResponse->getContent());
		self::assertTrue($responseData->success);
		foreach ($responseData as $key => $value)
		{
			self::assertArrayHasKey($key, $expected, print_r($responseData, true));
			self::assertEquals($expected[$key], $value, "Failed on key $key");
		}
	}

	/**
	 * @group User
	 */
	public function testUpdateUserInfo()
	{
		$this->setClient();

		//attempt to update a user while logged out
		if ($this->getLoggedIn())
		{
			$this->signout();
		}

		$updateObj = $this->createUpdateObject(self::NEW_FIRST_NAME, self::NEW_LAST_NAME, self::NEW_REGION_ID);

		$this->client->request('PUT', '/user/update', $updateObj);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_AUTH_REQUIRED);

		//create a new user to update
		$newUser = $this->createThrowawayUser();
		$this->loginAdhoc($newUser['email'], $newUser['password']);

		//$this->RemoveUser($newUser['email']);

		//update the user with the same information
		$updateObj = $this->createUpdateObject($newUser['first_name'], $newUser['last_name'], $newUser['region_id']);
		$this->client->request('PUT', '/user/update', $updateObj);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_BAD_REQUEST);

		//update the user with bad information
		$updateObj = $this->createUpdateObject($newUser['first_name'], $newUser['last_name'], self::BAD_REGION_ID);
		$this->client->request('PUT', '/user/update', $updateObj);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_BAD_REQUEST);

		//update the user with normal information
		$updateObj = $this->createUpdateObject(self::NEW_FIRST_NAME, self::NEW_LAST_NAME, self::NEW_REGION_ID);
		$this->client->request('PUT', '/user/update', $updateObj);
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);

		$response = json_decode($this->lastResponse->getContent());
		self::assertTrue($response->success);

		self::assertEquals(self::NEW_FIRST_NAME, $response->first_name, "first name did not match expected");
		self::assertEquals(self::NEW_LAST_NAME, $response->last_name, "last name did not match expected");
		self::assertEquals(self::NEW_REGION_ID, $response->region_id, "region id did not match expected");
		self::assertEquals(1, $response->country_id, "country did not match expected");
		self::assertNull($response->event_id, "event was not null");
		self::assertNull($response->team_id, "team was not null");

		$this->removeUser($newUser['email']);
	}

	private function createUpdateObject($firstName, $lastName, $regionId)
	{
		return [
			"first_name" => $firstName,
			"last_name"  => $lastName,
			"region_id"  => $regionId
		];
	}

}