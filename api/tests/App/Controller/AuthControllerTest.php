<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 11/22/2016
 * Time: 5:09 PM
 */

namespace TOETests\App\Controller;

use Exception;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\HTTPCodes;
use TOETests\BaseTestCase;
use TOETests\clsTesterCreds;

/**
 * Class AuthControllerTest
 * The following parameters get passed to each function in the $app['params'] array:
 *
 * 'register'        => [
 *   'email'      => public constants::SILEX_PARAM_STRING,
 *   'password'   => public constants::SILEX_PARAM_STRING,
 *   'first_name' => public constants::SILEX_PARAM_STRING,
 *   'last_name'  => public constants::SILEX_PARAM_STRING,
 *   'region_id'  => public constants::SILEX_PARAM_INT
 * ],
 * 'login'           => [
 *   'password' => public constants::SILEX_PARAM_STRING,
 *   'email'    => public constants::SILEX_PARAM_STRING
 * ],
 */
class AuthControllerTest extends BaseTestCase
{
	public const CORRECT_EMAIL   = "registerTest@test.com";
	public const GOOD_PASSWORD   = "password";
	public const GOOD_FIRST_NAME = "TheQuickBrownFox";
	public const GOOD_LAST_NAME  = "JumpsOverTheLazyRedDog";
	public const GOOD_REGION_ID  = 9;

	public const BAD_EMAIL     = "I'mMissingAnAtSign";
	public const BAD_PASSWORD  = "";
	public const BAD_REGION_ID = -1;

	public const SQL_INJECTION_PASSWORD = "' OR 1=1 OR password = '";

	/**
	 * @group Auth
	 */
	public function testRegister()
	{
		//test registering with correct data
		$registerObj = $this->getRegisterObject(
			self::CORRECT_EMAIL,
			self::GOOD_PASSWORD,
			self::GOOD_FIRST_NAME,
			self::GOOD_LAST_NAME,
			self::GOOD_REGION_ID
		);

		$this->setClient();

		$this->signout();

		if ($this->isUserRegistered(self::CORRECT_EMAIL))
		{
			$this->removeUser(self::CORRECT_EMAIL);
		}

		$this->client->request('POST', '/register', $registerObj);
		$this->basicResponseCheck(HTTPCodes::SUCCESS_RESOURCE_CREATED);

		//test registering with a user that already exists
		$this->client->request('POST', '/register', $registerObj);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_CONFLICT);

		$this->removeUser(self::CORRECT_EMAIL);

		//test registering with an incorrectly formatted request
		$registerObj = [
			'email'     => 'yes@no.com',
			'Password'  => "1231456",
			'firstname' => 'john'
		];

		$this->client->request('POST', '/register', $registerObj);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_BAD_REQUEST);

		$goodTestData = [
			[self::CORRECT_EMAIL, self::BAD_EMAIL],
			[self::GOOD_PASSWORD, ""],
			[$this->createBadString(), ""],
			[$this->createBadString(), ""],
			[self::GOOD_REGION_ID, self::BAD_REGION_ID]
		];

		for ($i = 0; $i < count($goodTestData); $i++)
		{
			for ($j = 1; $j < count($goodTestData[$i]); $j++)
			{
				$email = $goodTestData[0][0];
				$password = $goodTestData[1][0];
				$fname = $goodTestData[2][0];
				$lname = $goodTestData[3][0];
				$regId = $goodTestData[4][0];

				switch($i)
				{
					case 0;
						$email = $goodTestData[0][$j];
						break;
					case 1:
						$password = $goodTestData[1][$j];
						break;
					case 2;
						$fname = $goodTestData[2][$j];
						break;
					case 3:
						$lname = $goodTestData[3][$j];
						break;
					case 4:
						$regId = $goodTestData[4][$j];
						break;
					default:
						throw new Exception("Unimplemented 'i' value: $i");
				}

				$registerObj = $this->getRegisterObject(
					$email,
					$password,
					$fname,
					$lname,
					$regId
				);

				$this->client->request('POST', '/register', $registerObj);
				$this->checkPOSTResponse(HTTPCodes::CLI_ERR_BAD_REQUEST, $registerObj);
			}
		}
	}

	/**
	 * @group Auth
	 */
	public function testLogin()
	{
		$this->setClient();

		//test with a user that exists
		if ($this->getLoggedIn())
		{
			$this->signout();
		}

		$logginObj = $this->getLoginObject(
			clsTesterCreds::NORMAL_USER_EMAIL,
			clsTesterCreds::NORMAL_USER_PASSWORD
		);

		$this->client->request('POST', '/login', $logginObj);
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);
		$this->assertNotNull(json_decode($this->lastResponse->getContent())->token, "Token did not exist or was NULL in response");

		//test with a user that exists with a bad password
		$logginObj = $this->getLoginObject(
			clsTesterCreds::NORMAL_USER_EMAIL,
			clsTesterCreds::NORMAL_USER_PASSWORD . "hellodarknessmyoldfriend"
		);

		$this->client->request('POST', '/login', $logginObj);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_BAD_REQUEST);

		//test logging out of a user and logging in as another
		$this->signout();

		$logginObj = $this->getLoginObject(
			clsTesterCreds::SUPER_ADMIN_EMAIL,
			clsTesterCreds::SUPER_ADMIN_PASSWORD
		);

		$this->client->request('POST', '/login', $logginObj);
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);
		$this->assertNotNull(json_decode($this->lastResponse->getContent())->token, "Token did not exist or was NULL in response");

		//test with a user that does not exist
		$this->signout();

		$logginObj = $this->getLoginObject(
			"thisemaildoesnotexist@gmail.com",
			"password"
		);

		$this->client->request('POST', '/login', $logginObj);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_BAD_REQUEST);

		//test with attempted SQL injection

		$logginObj = $this->getLoginObject(
			clsTesterCreds::NORMAL_USER_EMAIL,
			self::SQL_INJECTION_PASSWORD
		);

		$this->client->request('POST', '/login', $logginObj);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_BAD_REQUEST);
	}

	/**
	 * Creates a string that should break normal SQL databases if they don't accept special encodings
	 *
	 * @return string
	 */
	private function createBadString()
	{
		$bad = "'\\/[]}{";
		$bad .= "\u{00C0}"; //A with an accent over it
		$bad .= "\u{0290}"; //upside down question mark
		return $bad;
	}

	private function getRegisterObject($email, $password, $firstName, $lastName, $regionId)
	{
		return [
			'email'      => $email,
			'password'   => $password,
			'first_name' => $firstName,
			'last_name'  => $lastName,
			'region_id'  => $regionId
		];
	}

	private function getLoginObject($email, $password)
	{
		return [
			'email'    => $email,
			'password' => $password
		];
	}

	private function isUserRegistered($email)
	{
		if (empty($email))
		{
			return false;
		}

		$this->setDatabaseConnection();
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('email');
		$qb->from('user');
		$qb->where("email = :email");
		$qb->setParameter('email', self::CORRECT_EMAIL, Constants::SILEX_PARAM_STRING);

		//var_dump($qb->getParameterTypes());

		$results = $qb->execute();

		return !empty($results->fetchAll());
	}

}