<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Danie
 * Date: 7/9/2017
 * Time: 1:15 PM
 */

namespace TOETests\App\Controller;

use TOE\GlobalCode\HTTPCodes;
use TOETests\BaseTestCase;
use TOETests\clsTesterCreds;

class RequestResetControllerTest extends BaseTestCase
{
	public const VALID_USER_EMAIL = clsTesterCreds::ADMIN_ON_TEAM_WITH_ROUTE_EMAIL;
    public const NON_EXISTENT_USER_EMAIL = "doesnotexist@notreal.com";

	/**
	 * @group Request-Password
	 */
	public function testRequestReset()
	{
		$this->setClient();

		//Test resetting an email that does not exist in the database
		$request = [
			'email' => self::NON_EXISTENT_USER_EMAIL
		];

		$this->client->request('POST', '/requestReset', $request);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_FOUND);


		//Test resetting a valid email address
		$request = [
			'email' => self::VALID_USER_EMAIL
		];

		$this->client->request('POST', '/requestReset', $request);
		$this->basicResponseCheck(HTTPCodes::SUCCESS_RESOURCE_CREATED);
	}
}