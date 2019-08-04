<?php
namespace TOE\App\Controller;

use Firebase\JWT\JWT;
use PHPMailerOAuth;
use Silex\Application;
use SMTP;
use TOE\Creds\clsCreds;
use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsHTTPCodes;
use TOE\GlobalCode\clsResponseJson;

class RequestResetController extends BaseController
{
	//Time until password reset token expires (in seconds)
	const VALID_TIME          = 18000;
	const MAX_ACTIVE_REQUESTS = 5;

	public function RequestReset(Application $app)
	{
		$this->InitializeInstance($app);
		$params = $app[clsConstants::PARAMETER_KEY];
		$userInfo = $app['user.lookup']->GetUserInfo($params['email'], ['user_id', 'first_name']);


		//check if user exists
		if ($userInfo === false)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "We couldn't find an account registered with that email."), clsHTTPCodes::CLI_ERR_NOT_FOUND);
		}

		//Get current time measured in the number of seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).
		$issuedAt = time(); //time of request
		$expiredAt = $issuedAt + self::VALID_TIME; // expired time

		//count existing requests (max 5)
		$result = $this->countRequests($userInfo['user_id'], $issuedAt);

		if ($result >= self::MAX_ACTIVE_REQUESTS)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "You have requested too many reset requests recently."), clsHTTPCodes::CLI_ERR_SPECIFIC_USER_REQUEST_OVERLOAD);
		}

		$data = [
			'iat'      => $issuedAt,
			'exp'      => $expiredAt,
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
		$qb = $this->db->createQueryBuilder();
		$qb->update('password_request')
			->set('status', ':status')
			->where('user_id = :user_id')
			->setParameter(':status', 'used', clsConstants::SILEX_PARAM_STRING)
			->setParameter(':user_id', $userInfo['user_id']);
		$qb->execute();


		//Insert the request into the database
		$qb = $this->db->createQueryBuilder();
		$qb->insert('password_request')
			->values([
				'user_id'    => ':user_id',
				'issued_at'  => ':issued_at',
				'expired_at' => ':expired_at',
				'unique_id'  => ':unique_id'
			])
			->setParameter(':user_id', $userInfo['user_id'])
			->setParameter('issued_at', $issuedAt)
			->setParameter(':expired_at', $expiredAt)
			->setParameter(':unique_id', $data['uniqueID'], clsConstants::SILEX_PARAM_STRING);
		$result = $qb->execute();

		if ($result === 0)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "An error occurred on our end."), clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		}

		$message = $this->generateResetEmailHTMLBody($jwt, $userInfo['first_name']);
		$email = $this->getUserEmail($userInfo['user_id']);
		$success = $this->emailToken($message, $email);
		if ($success === true)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(true, ""), clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
		}

		$this->logger->error($success);
		return $app->json(clsResponseJson::GetJsonResponseArray(false, "An error occurred when trying to send the email: $success"), clsHTTPCodes::SERVER_SERVICE_UNAVAILABLE);
	}

	/**
	 * Returns the number of active reset requests for the passed in user
	 *
	 * @param int $userID
	 * @param int $requestTime The time being compared to the expire time
	 *
	 * @return int The number of active password reset requests
	 */
	private function countRequests($userID, $requestTime)
	{
		$qb = $this->db->createQueryBuilder();
		$qb->select('count(*) as count')
			->from('password_request')
			->where('user_id = :user_id')
			->andWhere('expired_at > :requestTime')
			->andWhere('status != :used')
			->setParameter(':user_id', $userID)
			->setParameter(':used', 'used')
			->setParameter(':requestTime', $requestTime);

		return $qb->execute()->fetch()['count'];
	}

	/**
	 * Emails the reset token the specified
	 *
	 * @param string $message
	 * @param string $email
	 *
	 * @return bool|string Returns true on success, and an error message on false.
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

		$mail->oauthUserEmail = clsCreds::RESET_ACCOUNT_EMAIL;
		$mail->oauthClientId = clsCreds::RESET_CLIENT_ID;
		$mail->oauthClientSecret = clsCreds::RESET_CLIENT_SECRET;
		$mail->oauthRefreshToken = clsCreds::RESET_REFRESH_TOKEN;

		$mail->SMTPOptions = [
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
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

		if ($mail->send() === false)
		{
			return $mail->ErrorInfo;
		};

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

		if ($results === false || empty($results))
		{
			return false;
		}

		return $results['email'];
	}

	/**
	 * Generates an HTML page containing the link to reset a user's password.
	 *
	 * @param string $token The jwt token that will be used for verification
	 *
	 * @param string $username The name of the user who's email was used for the password reset request
	 *
	 * @return string
	 */
	private function generateResetEmailHTMLBody($token, $username)
	{
		$email = file_get_contents(__DIR__ . '/../../email-templates/reset-password-email.html');
		$email = str_replace("%reset-link%", $_SERVER['SERVER_PROTOCOL'] . $_SERVER['SERVER_NAME'] . "/" . clsConstants::EMAIL_RESET_LINK . $token, $email);
		return str_replace("%username%", $username, $email);
	}
}

?>