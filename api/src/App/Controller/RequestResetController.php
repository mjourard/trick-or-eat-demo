<?php
declare(strict_types=1);

namespace TOE\App\Controller;

use DateTime;
use DateTimeZone;
use Silex\Application;
use TOE\App\Service\Email\aClient;
use TOE\App\Service\Email\EmailException;
use TOE\App\Service\Email\Message;
use TOE\App\Service\Password\PasswordRequestManager;
use TOE\App\Service\Password\WebToken;
use TOE\App\Service\User\UserLookupService;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\Env;
use TOE\GlobalCode\HTTPCodes;
use TOE\GlobalCode\ResponseJson;

class RequestResetController extends BaseController
{
	public function requestReset(Application $app)
	{
		$this->initializeInstance($app);
		$params = $app[Constants::PARAMETER_KEY];
		$logCtx = ['email' => $params['email']];
		$this->logger->debug("Getting user info", $logCtx);
		/** @var UserLookupService $userLookup */
		$userLookup = $app['user.lookup'];
		$userInfo = $userLookup->getUserInfo($params['email'], ['user_id', 'first_name']);

		//check if user exists
		if($userInfo === false)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "We couldn't find an account registered with that email."), HTTPCodes::CLI_ERR_NOT_FOUND);
		}

		/** @var PasswordRequestManager $pwRequestManager */
		$pwRequestManager = $app['password.request'];

		//Get current time for the 'issued at' time
		$issuedAt = new DateTime('now', new DateTimeZone('utc')); //time of request
		$expiredAt = $pwRequestManager->getExpireTime($issuedAt);

		$this->logger->debug("Counting requests", $logCtx);
		if($pwRequestManager->maxRequestsExceeded($userInfo['user_id'], $issuedAt))
		{
			$this->logger->warn("User has issued too many password reset requests", ['user_id' => $userInfo['user_id'], 'issued_at' => $issuedAt->format('Y-m-d H:i:s')]);
			return $app->json(ResponseJson::getJsonResponseArray(false, "You have requested too many reset requests recently."), HTTPCodes::CLI_ERR_SPECIFIC_USER_REQUEST_OVERLOAD);
		}

		//Create JSON webtoken
		$jwt = new WebToken($issuedAt, $expiredAt, $userInfo['user_id']);

		//invalidate all previous reset requests
		$this->logger->debug("invalidating previous password reset requests", $logCtx);
		$pwRequestManager->updateUserResetRequests($userInfo['user_id'], PasswordRequestManager::REQUEST_STATUS_USED);


		//Insert the request into the database
		$this->logger->debug("saving new reset request into the database", $logCtx);
		if(!$pwRequestManager->insertResetRequest($userInfo['user_id'], $issuedAt, $expiredAt, $jwt->getUniqueId()))
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "An error occurred on our end."), HTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		}

		$message = $this->generateResetEmailHTML($jwt->encode($app['jwt.key']), $userInfo['first_name']);
		$this->logger->debug("Sending user email", $logCtx);
		$success = $this->emailToken($message, $params['email'], $app['email']);
		if($success === true)
		{
			$this->logger->debug("sent reset email", $logCtx);
			return $app->json(ResponseJson::getJsonResponseArray(true, ""), HTTPCodes::SUCCESS_RESOURCE_CREATED);
		}

		$this->logger->error($success);
		return $app->json(ResponseJson::getJsonResponseArray(false, "An error occurred when trying to send the email"), HTTPCodes::SERVER_SERVICE_UNAVAILABLE);
	}

	/**
	 * Emails the reset token the specified
	 *
	 * @param string  $message
	 * @param string  $email
	 *
	 * @param aClient $client An email client to send the reset token message from
	 *
	 * @return bool|string Returns true on success, and an error message on false.
	 */
	private function emailToken($message, $email, aClient $client)
	{
		$messageToSend = (new Message())
			->setTo($email)
			->setFrom(Env::get(Env::TOE_RESET_ACCOUNT_EMAIL))
			->setFromName('Guelph Trick or Eat')
			->setSubject('Password Reset')
			->setBody($message);

		try
		{
			$client->sendEmail($messageToSend);
			return true;
		}
		catch(EmailException $ex)
		{
			return $ex->getMessage();
		}
	}

	/**
	 * Generates an HTML page containing the link to reset a user's password.
	 *
	 * @param string $token    The jwt token that will be used for verification
	 *
	 * @param string $username The name of the user who's email was used for the password reset request
	 *
	 * @return string
	 */
	private function generateResetEmailHTML($token, $username)
	{
		$email = file_get_contents(__DIR__ . '/../../email-templates/reset-password-email.html');
		$baseUrl = Env::get(Env::TOE_ACCESS_CONTROL_ALLOW_ORIGIN);
		if(empty($baseUrl) && !empty($_SERVER['HTTP_ORIGIN']))
		{
			$baseUrl = $_SERVER['HTTP_ORIGIN'];
		}
		if(empty($baseUrl) && !empty($_SERVER['HTTP_REFERER']))
		{
			$baseUrl = $_SERVER['HTTP_REFERER'];
		}
		$baseUrl = rtrim($baseUrl, "/");
		if(empty($baseUrl))
		{
			$this->logger->error("empty base url used when generating password reset email html body");
		}
		$email = str_replace("%reset-link%", $baseUrl . "/" . Constants::EMAIL_RESET_LINK . $token, $email);

		return str_replace("%username%", $username, $email);
	}
}
