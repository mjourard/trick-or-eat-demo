<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 1/13/2017
 * Time: 9:41 AM
 */
namespace TOETests\App\Controller;

use TOE\GlobalCode\clsHTTPCodes;
use TOETests\BaseTestCase;
use TOETests\clsTesterCreds;


/**
 * Class UserControllerTest
 * The following parameters get passed to each function in the $app['params'] array:
 *
 * 'user/update' => [
 *     'first_name' => clsConstants::SILEX_PARAM_STRING,
 *     'last_name' => clsConstants::SILEX_PARAM_STRING,
 *     'region_id' => clsConstants::SILEX_PARAM_INT
 *  ]
 */
class UserControllerTest extends BaseTestCase
{
	const CUR_FIRST_NAME = "normal";
	const CUR_LAST_NAME  = "user";
	const CUR_REGION_ID  = 9;

	const NEW_FIRST_NAME = "abnormal";
	const NEW_LAST_NAME  = "non-user";
	const NEW_REGION_ID  = 1;

	const BAD_REGION_ID = 10000000;

	/**
	 * @group User
	 */
	public function testGetUserInfo()
	{
		$this->SetClient();
		//try without any creds
		if ($this->GetLoggedIn())
		{
			$this->Signout();
		}

		$this->client->request('GET', '/user/userInfo');
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_AUTH_REQUIRED);

		//confirm all data returned is the correct data
		$this->Login(clsTesterCreds::NORMAL_USER_EMAIL);
		$this->client->request('GET', '/user/userInfo');
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);

		$expected = [
			'user_roles'   => ['participant', 'driver'],
			'first_name'   => 'normal',
			'last_name'    => 'user',
			'region_id'    => 9,
			'event_id'     => null,
			'event_name'   => null,
			'team_id'      => null,
			'team_name'    => null,
			'region_name'  => "Ontario",
			'country_id'   => 1,
			'country_name' => "Canada",
			'email'        => "normaluser@gmail.com",
			'id'           => 2,
			'success'      => true,
			'message'      => ''

		];

		$responseData = json_decode($this->lastResponse->getContent());
		$this->assertTrue($responseData->success);
		foreach ($responseData as $key => $value)
		{
			$this->assertArrayHasKey($key, $expected, print_r($responseData, true));
			$this->assertEquals($expected[$key], $value, "Failed on key $key");
		}
	}

	/**
	 * @group User
	 */
	public function testUpdateUserInfo()
	{
		$this->SetClient();

		//attempt to update a user while logged out
		if ($this->GetLoggedIn())
		{
			$this->Signout();
		}

		$updateObj = $this->CreateUpdateObject(self::NEW_FIRST_NAME, self::NEW_LAST_NAME, self::NEW_REGION_ID);

		$this->client->request('PUT', '/user/update', $updateObj);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_AUTH_REQUIRED);

		//create a new user to update
		$newUser = $this->CreateThrowawayUser();
		$this->LoginAdhoc($newUser['email'], $newUser['password']);

		//$this->RemoveUser($newUser['email']);

		//update the user with the same information
		$updateObj = $this->CreateUpdateObject($newUser['first_name'], $newUser['last_name'], $newUser['region_id']);
		$this->client->request('PUT', '/user/update', $updateObj);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);

		//update the user with bad information
		$updateObj = $this->CreateUpdateObject($newUser['first_name'], $newUser['last_name'], self::BAD_REGION_ID);
		$this->client->request('PUT', '/user/update', $updateObj);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);

		//update the user with normal information
		$updateObj = $this->CreateUpdateObject(self::NEW_FIRST_NAME, self::NEW_LAST_NAME, self::NEW_REGION_ID);
		$this->client->request('PUT', '/user/update', $updateObj);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);

		$response = json_decode($this->lastResponse->getContent());
		$this->assertTrue($response->success);

		$this->assertEquals($response->first_name, self::NEW_FIRST_NAME, "first name did not match expected");
		$this->assertEquals($response->last_name, self::NEW_LAST_NAME, "last name did not match expected");
		$this->assertEquals($response->region_id, self::NEW_REGION_ID, "region id did not match expected");
		$this->assertEquals($response->country_id, 1, "country did not match expected");
		$this->assertNull($response->event_id, "event was not null");
		$this->assertNull($response->team_id, "team was not null");

		$this->RemoveUser($newUser['email']);
	}

	private function CreateUpdateObject($firstName, $lastName, $regionId)
	{
		return [
			"first_name" => $firstName,
			"last_name"  => $lastName,
			"region_id"  => $regionId
		];
	}

}