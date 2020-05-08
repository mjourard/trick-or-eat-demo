<?php
declare(strict_types=1);

namespace TOE\App\Controller;
/**
 * Created by PhpStorm.
 * User: Danie
 * Date: 7/9/2017
 * Time: 5:05 PM
 */

use Exception;
use Silex\Application;
use TOE\App\Service\Password\PasswordRequestManager;
use TOE\App\Service\Password\WebToken;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\HTTPCodes;
use TOE\GlobalCode\ResponseJson;

class ResetPasswordController extends BaseController
{
	public function resetPassword(Application $app)
	{
		$this->initializeInstance($app);
		$password = $app[Constants::PARAMETER_KEY]['password'];
		$jwt = $app[Constants::PARAMETER_KEY]['jwt'];
		$currentTime = new \DateTime('now', new \DateTimeZone('utc'));
		/** @var PasswordRequestManager $pwRequestManager */
		$pwRequestManager = $app['password.request'];

		try
		{
			$token = WebToken::decode($jwt, $app['jwt.key']);
			$pwRequestManager->checkValidToken($token, $currentTime);
		}
		catch(\Exception $e)
		{
			$this->logger->warning($e->getMessage(), ['jwt' => $jwt]);
			return $app->json(ResponseJson::GetJsonResponseArray(false, "The token passed in is no longer valid."), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		try
		{
			$pwRequestManager->resetPasswordByToken($token, $password);
			return $app->json(ResponseJson::GetJsonResponseArray(true, ""), HTTPCodes::SUCCESS_NO_CONTENT);
		}
		catch(Exception $ex)
		{
			$this->logger->err("Unable to update user's password after verifying their reset token was correct", [
				'user_id' => $this->userInfo->getID(),
				'err' => $ex->getMessage()
			]);
			return $app->json(ResponseJson::GetJsonResponseArray(false, "Failed to update your password. Contact TOE support."), HTTPCodes::SERVER_SERVICE_UNAVAILABLE);
		}
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
		/** @var PasswordRequestManager $pwRequestManager */
		$pwRequestManager = $app['password.request'];

		try
		{
			$token = WebToken::decode($token, $app['jwt.key']);
			$pwRequestManager->checkValidToken($token, $currentTime);
		}
		catch(\Exception $e)
		{
			$this->logger->warning($e->getMessage(), ['jwt' => $token]);
			return $app->json(ResponseJson::GetJsonResponseArray(false, "Token is invalid: " . $e->getMessage()), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		return $app->json(ResponseJson::GetJsonResponseArray(true, ""), HTTPCodes::SUCCESS_NO_CONTENT);
	}
}