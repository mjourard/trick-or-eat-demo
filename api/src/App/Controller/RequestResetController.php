<?php

namespace TOE\App\Controller;

use DateInterval;
use DateTime;
use DateTimeZone;
use Firebase\JWT\JWT;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PHPMailerOAuth;
use Silex\Application;
use SMTP;
use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsEnv;
use TOE\GlobalCode\clsHTTPCodes;
use TOE\GlobalCode\clsResponseJson;

class RequestResetController extends BaseController
{
	//Time until password reset token expires (in seconds)
	const VALID_TIME = 18000;
	const MAX_ACTIVE_REQUESTS = 5;

	public function RequestReset(Application $app)
	{
		$this->InitializeInstance($app);
		$params = $app[clsConstants::PARAMETER_KEY];
		$logCtx = ['email' => $params['email']];
		$this->logger->debug("Getting user info", $logCtx);
		$userInfo = $app['user.lookup']->GetUserInfo($params['email'], ['user_id', 'first_name']);


		//check if user exists
		if($userInfo === false)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "We couldn't find an account registered with that email."), clsHTTPCodes::CLI_ERR_NOT_FOUND);
		}

		//Get current time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).
		$issuedAt = new DateTime('now', new DateTimeZone('utc')); //time of request
		$expiredAt = clone $issuedAt;
		$expiredAt->add(new DateInterval('PT' . self::VALID_TIME . 'S'));

		//count existing requests (max 5)
		$this->logger->debug("Counting requests", $logCtx);
		$result = $this->countRequests($userInfo['user_id'], $issuedAt);

		if($result >= self::MAX_ACTIVE_REQUESTS)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "You have requested too many reset requests recently."), clsHTTPCodes::CLI_ERR_SPECIFIC_USER_REQUEST_OVERLOAD);
		}

		$data = [
			'iat'      => $issuedAt->getTimestamp(),
			'exp'      => $expiredAt->getTimestamp(),
			'userID'   => $userInfo['user_id'],
			'uniqueID' => uniqid()
		];

		//Create JSON webtoken
		$jwt = JWT::encode(
			$data,
			$app['jwt.key'],
			'HS512'
		);

		//invalidate all previous reset requests
		$this->logger->debug("invalidating previous password reset requests", $logCtx);
		$qb = $this->db->createQueryBuilder();
		$qb->update('password_request')
			->set('status', ':status')
			->where('user_id = :user_id')
			->setParameter(':status', 'used', clsConstants::SILEX_PARAM_STRING)
			->setParameter(':user_id', $userInfo['user_id']);
		$qb->execute();


		//Insert the request into the database
		$this->logger->debug("saving new reset request into the database", $logCtx);
		$qb = $this->db->createQueryBuilder();
		$qb->insert('password_request')
			->values([
				'user_id'    => ':user_id',
				'issued_at'  => 'FROM_UNIXTIME(:issued_at)',
				'expired_at' => 'FROM_UNIXTIME(:expired_at)',
				'unique_id'  => ':unique_id'
			])
			->setParameter(':user_id', $userInfo['user_id'])
			->setParameter(':issued_at', $issuedAt->getTimestamp())
			->setParameter(':expired_at', $expiredAt->getTimestamp())
			->setParameter(':unique_id', $data['uniqueID'], clsConstants::SILEX_PARAM_STRING);
		$result = $qb->execute();

		if($result === 0)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "An error occurred on our end."), clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		}

		$this->logger->debug("generating reset email html", $logCtx);
		$message = $this->generateResetEmailHTMLBody($jwt, $userInfo['first_name']);
		$this->logger->debug("getting user email", $logCtx);
		$email = $this->getUserEmail($userInfo['user_id']);
		$this->logger->debug("Sending user email", $logCtx);
		$success = $this->emailToken($message, $email);
		$this->logger->debug("reset email sent successfully", $logCtx);
		if($success === true)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(true, ""), clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
		}

		$this->logger->error($success);

		return $app->json(clsResponseJson::GetJsonResponseArray(false, "An error occurred when trying to send the email: $success"), clsHTTPCodes::SERVER_SERVICE_UNAVAILABLE);
	}

	/**
	 * Returns the number of active reset requests for the passed in user
	 *
	 * @param int      $userID
	 * @param DateTime $requestTime The time being compared to the expire time
	 *
	 * @return int The number of active password reset requests
	 */
	private function countRequests($userID, $requestTime)
	{
		$qb = $this->db->createQueryBuilder();
		$qb->select('count(*) as count')
			->from('password_request')
			->where('user_id = :user_id')
			->andWhere('expired_at > FROM_UNIXTIME(:requestTime)')
			->andWhere('status != :used')
			->setParameter(':user_id', $userID)
			->setParameter(':used', 'used')
			->setParameter(':requestTime', $requestTime->getTimestamp());

		return $qb->execute()->fetch()['count'];
	}

	/**
	 * Emails the reset token the specified
	 *
	 * @param string $message
	 * @param string $email
	 *
	 * @return bool|string Returns true on success, and an error message on false.
	 * @throws \phpmailerException
	 * @throws IdentityProviderException
	 */
	private function emailToken($message, $email)
	{
		$mail = new PHPMailerOAuth();
		// 1 = messages only
		// 2 = errors + messages
		// 3 = detailed errors + messages
		$mail->isSMTP();
		//TODO: move this to a config file like the other configurable constants
		$mail->SMTPDebug = SMTP::DEBUG_OFF;

		$mail->oauthUserEmail = clsEnv::Get(clsEnv::TOE_RESET_ACCOUNT_EMAIL);
		$mail->oauthClientId = clsEnv::Get(clsEnv::TOE_RESET_CLIENT_ID);
		$mail->oauthClientSecret = clsEnv::Get(clsEnv::TOE_RESET_CLIENT_SECRET);
		$mail->oauthRefreshToken = clsEnv::Get(clsEnv::TOE_RESET_REFRESH_TOKEN);

		$mail->SMTPOptions = [
			'ssl' => [
				'verify_peer'       => false,
				'verify_peer_name'  => false,
				'allow_self_signed' => true
			]
		];

		$mail->SMTPAuth = true;                  // enable SMTP authentication
		$mail->SMTPSecure = "tls";            //use tls protocol
		$mail->Host = "smtp.gmail.com";      // SMTP server
		$mail->Port = 587;                   // SMTP port
		$mail->AuthType = 'XOAUTH2';

		$mail->setFrom('trickoreat@mealexchange.com', 'Meal Exchange');
		$mail->Subject = "Password Reset";
		$mail->msgHTML($message);
		$mail->addAddress($email);

		try
		{
			if($mail->send() === false)
			{
				return $mail->ErrorInfo;
			}
		}
		catch(IdentityProviderException $ex)
		{
			$this->logger->error("Reset Password Email Failed: " . $ex->getMessage(), ['email' => $email]);
			if(clsEnv::Get(clsEnv::TOE_DEBUG_ON))
			{
				return true;
			}
			throw $ex;
		}

		return true;
	}

	/**
	 * Gets the email address associated with the passed in userId
	 *
	 * @param int $userid The id of the user that you want the email of.
	 *
	 * @return string|bool Returns the email address associated with that user_id, or false if that user_id doesn't exist.
	 */
	private function getUserEmail($userid)
	{
		$qb = $this->db->createQueryBuilder();
		$qb->select('email')
			->from('user')
			->where('user_id = :userid')
			->setParameter('userid', $userid, clsConstants::SILEX_PARAM_STRING);
		$results = $qb->execute()->fetch();

		if($results === false || empty($results))
		{
			return false;
		}

		return $results['email'];
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
	private function generateResetEmailHTMLBody($token, $username)
	{
		$email = file_get_contents(__DIR__ . '/../../email-templates/reset-password-email.html');
		$baseUrl = clsEnv::Get(clsEnv::TOE_ACCESS_CONTROL_ALLOW_ORIGIN);
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
		$email = str_replace("%reset-link%", $baseUrl . "/" . clsConstants::EMAIL_RESET_LINK . $token, $email);

		return str_replace("%username%", $username, $email);
	}
}
