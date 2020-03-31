<?php
/**
 * Created by PhpStorm.
 * User: Danie
 * Date: 7/16/2017
 * Time: 10:15 PM
 */

namespace TOETests\App\Controller;

use DateInterval;
use DateTime;
use DateTimeZone;
use TOE\GlobalCode\clsHTTPCodes;
use TOETests\BaseTestCase;
use Firebase\JWT\JWT;
use TOE\GlobalCode\clsConstants;
use TOETests\clsTesterCreds;


const TEST_USER_ID = 7;
const NEW_PASSWORD = "12345";
const VALID_TIME = 16000;

class ResetPasswordControllerTest extends BaseTestCase
{
	/**
	 * @group Reset-Password
	 */
	public function testResetPassword()
	{
		$app = $this->CreateApplication();
		$this->SetDatabaseConnection();
		$this->SetClient();

		//testing with an invalid token
		$request = [
			'jwt'      => $this->createInvalidResetToken($app['jwt.key']),
			'password' => 'password'
		];

		$this->client->request('POST', '/resetPassword', $request);
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);

		//Test with a valid token
		//get the old password
		$oldPass = clsTesterCreds::GENERIC_PASSWORD;
		$request = [
			'jwt'      => $this->createValidResetToken($app['jwt.key']),
			'password' => 'password'
		];

		$this->client->request('POST', '/resetPassword', $request);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_NO_CONTENT);

		//reset to the old password
		$request = [
			'jwt'      => $this->createValidResetToken($app['jwt.key']),
			'password' => $oldPass
		];

		$this->client->request('POST', '/resetPassword', $request);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_NO_CONTENT);
	}

	/**
	 * @group Reset-Password
	 */
	public function testCheckTokenStatus()
	{
		$this->markTestIncomplete("Not Implemented");
	}

	private function createValidResetToken($key)
	{
		//Get current time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).
		$issuedAt = new DateTime('now', new DateTimeZone('utc')); //time of request
		$expiredAt = clone $issuedAt;
		$expiredAt->add(new DateInterval('PT1600S'));
		$uid = uniqid();

		$jwt = $this->createResetToken($key, $issuedAt, $expiredAt, TEST_USER_ID, $uid);

		//Insert the request into the database
		$qb = $this->dbConn->createQueryBuilder();
		$qb->insert('password_request')
			->values([
				'user_id'    => ':user_id',
				'issued_at'  => ':issued_at',
				'expired_at' => ':expired_at',
				'unique_id'  => ':unique_id'
			])
			->setParameter(':user_id', TEST_USER_ID)
			->setParameter('issued_at', $issuedAt->format(clsConstants::DT_FORMAT))
			->setParameter(':expired_at', $expiredAt->format(clsConstants::DT_FORMAT))
			->setParameter(':unique_id', $uid, clsConstants::SILEX_PARAM_STRING);
		$qb->execute();

		return $jwt;
	}

	private function createInvalidResetToken($key)
	{
		$issuedAt = new DateTime('now', new DateTimeZone('utc'));
		$expiredAt = clone $issuedAt;
		$expiredAt->sub(new DateInterval('PT1600S'));
		return $this->createResetToken($key, $issuedAt, $expiredAt, TEST_USER_ID, uniqid());
	}

	private function createResetToken($key, DateTime $issuedAt, DateTime $expiredAt, $userId, $uniqueId)
	{
		$data = [
			'iat'      => $issuedAt->getTimestamp(),         //issued time
			'exp'      => $expiredAt->getTimestamp(),         //expired time
			'userID'   => $userId,       // user id
			'uniqueID' => $uniqueId          //token id

		];

		//Create JSON webtoken
		return JWT::encode(
			$data,
			$key,
			'HS512'
		);
	}
}
