<?php

namespace TOE\App\Controller;
/**
 * Created by PhpStorm.
 * User: Danie
 * Date: 7/9/2017
 * Time: 5:05 PM
 */

use DateTime;
use Exception;
use Silex\Application;
use \Firebase\JWT\JWT;
use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsHTTPCodes;
use TOE\GlobalCode\clsResponseJson;
use TOETests\clsTestConstants;

class ResetPasswordController extends BaseController
{
	public function resetPassword(Application $app)
	{
		$this->initializeInstance($app);
		$password = $app[clsConstants::PARAMETER_KEY]['password'];
		$jwt = $app[clsConstants::PARAMETER_KEY]['jwt'];
		$currentTime = new \DateTime('now', new \DateTimeZone('utc'));

		try
		{
			$data = $this->decodeResetToken($jwt, $app['jwt.key']);
		}
		catch(\Exception $e)
		{
			$this->logger->warning($e->getMessage(), ['jwt' => $jwt]);

			return $app->json(clsResponseJson::GetJsonResponseArray(false, "The token passed in is no longer valid."), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		$errMessage = $this->isValidToken($data, $currentTime);

		if($errMessage !== true)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, $errMessage), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		//Update user's account with new password
		//use a transaction
		$qb = $this->db->createQueryBuilder();
		$qb->update('user')
			->set('password', ':password')
			->where('user_id = :user_id')
			->setParameter(':user_id', $data->userID)
			->setParameter(':password', password_hash($password, PASSWORD_DEFAULT));
		$qb->execute();

		$qb = $this->db->createQueryBuilder();
		$qb->update('password_request')
			->set('status', ':status')
			->where('user_id = :user_id')
			->andWhere('unique_id = :unique_id')
			->andWhere('issued_at = FROM_UNIXTIME(:issued_at)')
			->andWhere('expired_at = FROM_UNIXTIME(:expired_at)')
			->setParameter(':status', 'used', clsConstants::SILEX_PARAM_STRING)
			->setParameter(':user_id', $data->userID)
			->setParameter(':unique_id', $data->uniqueID, clsConstants::SILEX_PARAM_STRING)
			->setParameter(':issued_at', $data->iat->getTimestamp())
			->setParameter(':expired_at', $data->exp->getTimestamp());
		$qb->execute();

		return $app->json(clsResponseJson::GetJsonResponseArray(true, ""), clsHTTPCodes::SUCCESS_NO_CONTENT);
	}

	/**
	 * Called to check the status of the token being passed in.
	 * Returns a 204 status code if the token is still valid, and 400 error code if something about the token is invalid.
	 *
	 * @param \Silex\Application $app
	 *
	 * @param string             $token The token being checked
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 * @throws Exception
	 */
	public function checkTokenStatus(Application $app, $token)
	{
		$this->initializeInstance($app);
		$currentTime = new \DateTime('now', new \DateTimeZone('utc'));

		try
		{
			$data = $this->decodeResetToken($token, $app['jwt.key']);
		}
		catch(\Exception $e)
		{
			$this->logger->warning($e->getMessage(), ['jwt' => $token]);

			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Token is invalid: " . $e->getMessage()), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		$errMessage = $this->isValidToken($data, $currentTime);

		if($errMessage !== true)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, $errMessage), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, ""), clsHTTPCodes::SUCCESS_NO_CONTENT);
	}

	/**
	 * Checks if the passed in token can be used to reset a password.
	 *
	 * @param object   $token       The token being checked
	 * @param DateTime $currentTime The time that the request was sent in
	 *
	 * @return bool|string Returns true if the token can used to reset a password, and a string detailing why the token is invalid if not
	 */
	private function isValidToken($token, $currentTime)
	{
		//verify the request exists
		$qb = $this->db->createQueryBuilder();
		$qb->select([
			'expired_at',
			'status'
		])
			->from('password_request')
			->where('user_id = :user_id')
			->andWhere('unique_id = :unique_id')
			->andWhere('issued_at = FROM_UNIXTIME(:issued_at)')
			->andWhere('expired_at = FROM_UNIXTIME(:expired_at)')
			->setParameter(':user_id', $token->userID)
			->setParameter(':unique_id', $token->uniqueID, clsConstants::SILEX_PARAM_STRING)
			->setParameter(':issued_at', $token->iat->getTimestamp())
			->setParameter(':expired_at', $token->exp->getTimestamp());
		$result = $qb->execute()->fetchAll();


		if(empty($result))
		{
			return "An expired reset token has been used. Request another reset";
		}
		$expiredAt = DateTime::createFromFormat(clsConstants::DT_FORMAT, $result[0]['expired_at']);
		if($expiredAt < $currentTime)
		{
			return "An expired reset token has been used. Request another reset";
		}

		if($result[0]['status'] === 'used')
		{
			return "This reset token has already been used. Request another reset";
		}

		return true;
	}

	/**
	 * Decodes the passed in token and checks for all required fields
	 *
	 * @param string $token The token being decoded
	 * @param string $key   The key used to decode the token
	 *
	 * @return object The decoded JWT as an associative array
	 * @throws \Exception
	 */
	private function decodeResetToken($token, $key)
	{
		$data = JWT::decode($token, $key, ['HS512']);
		$elements = [
			'iat',
			'exp',
			'userID',
			'uniqueID'
		];
		foreach($elements as $element)
		{
			if(!property_exists($data, $element))
			{
				throw new Exception("Token did not contain element '$element'");
			}
		}
		$iatDT = new DateTime('now', new \DateTimeZone('utc'));
		$iatDT->setTimestamp($data->iat);
		$data->iat = $iatDT;

		$expDT = clone $iatDT;
		$expDT->setTimestamp($data->exp);
		$data->exp = $expDT;

		return $data;
	}
}