<?php
/**
 * Created by PhpStorm.
 * User: Danie
 * Date: 7/9/2017
 * Time: 1:15 PM
 */

namespace TOETests\App\Controller;

use TOE\GlobalCode\clsHTTPCodes;
use TOETests\BaseTestCase;
use TOETests\clsTesterCreds;

class RequestResetControllerTest extends BaseTestCase
{
	const VALID_USER_EMAIL = clsTesterCreds::ADMIN_ON_TEAM_WITH_ROUTE_EMAIL;
    const NON_EXISTENT_USER_EMAIL = "doesnotexist@notreal.com";

	/**
	 * @group Request-Password
	 */
	public function testRequestReset()
	{
		$this->SetClient();

		//Test resetting an email that does not exist in the database
		$request = [
			'email' => self::NON_EXISTENT_USER_EMAIL
		];

		$this->client->request('POST', '/requestReset', $request);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_NOT_FOUND);


		//Test resetting a valid email address
		$request = [
			'email' => self::VALID_USER_EMAIL
		];

		$this->client->request('POST', '/requestReset', $request);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
	}
}