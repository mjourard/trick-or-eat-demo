<?php
declare(strict_types=1);

namespace TOE\App\Controller;

use Silex\Application;
use \Firebase\JWT\JWT;
use Symfony\Component\Validator\Constraints as Assert;
use TOE\App\Service\Location\RegionManager;
use TOE\App\Service\User\UserException;
use TOE\App\Service\User\UserLookupService;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\ResponseJson;
use TOE\GlobalCode\HTTPCodes;

class AuthController extends BaseController
{
	public const VALID_TIME = 18000;

	/**
	 * Registers a new user for the trick-or-eat application.
	 *
	 * @param Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function register(Application $app)
	{
		if(!$this->emailIsGood($app[Constants::PARAMETER_KEY]['email'], $app))
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "Bad email"), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		if(!$this->passwordIsGood($app[Constants::PARAMETER_KEY]['password']))
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "Bad password"), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		$firstName = trim($app[Constants::PARAMETER_KEY]['first_name']);
		if(empty($firstName))
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "First name cannot be empty."), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		$lastName = trim($app[Constants::PARAMETER_KEY]['last_name']);
		if(empty($lastName))
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "Last name cannot be empty."), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		$email = strtolower(trim($app[Constants::PARAMETER_KEY]['email']));

		$this->initializeInstance($app);

		/** @var UserLookupService $userLookup */
		$userLookup = $app['user.lookup'];
		$userId = $userLookup->getUserId($email);

		if($userId !== false)
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "email of '$email' already registered"), HTTPCodes::CLI_ERR_CONFLICT);
		}

		//verify the region passed in exists
		/** @var RegionManager $regionManager */
		$regionManager = $app['region'];
		if(!$regionManager->regionExists($app[Constants::PARAMETER_KEY]['region_id']))
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "region_id of {$app[Constants::PARAMETER_KEY]['region_id']} is was not found in the database."), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		try
		{
			$userId = $userLookup->registerUser($email, $app[Constants::PARAMETER_KEY]['password'], $firstName, $lastName, $app[Constants::PARAMETER_KEY]['region_id']);
			return $app->json(ResponseJson::GetJsonResponseArray(true, "registration successful", ['user_id' => $userId]), HTTPCodes::SUCCESS_RESOURCE_CREATED);
		}
		catch(UserException $ex)
		{
			$this->logger->err($ex->getMessage(), [
				'email' => $email
			]);
			return $app->json(ResponseJson::GetJsonResponseArray(false, 'There was a problem registering the user\'s role.'), HTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		}
	}

	/**
	 * Takes in an email and password from a request and returns a response containing either a login token or an error message
	 *
	 * @param Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function login(Application $app)
	{
		$this->initializeInstance($app);
		/** @var UserLookupService $userLookup */
		$userLookup = $app['user.lookup'];
		try
		{
			$userInfo = $userLookup->getUserInfo(
				strtolower($app[Constants::PARAMETER_KEY]["email"]),
				['user_id', 'email', 'password'],
				true
			);
		}
		catch(UserException $ex)
		{
			$this->logger->err($ex->getMessage(), ['email' => $app[Constants::PARAMETER_KEY]["email"]]);
			return $app->json(ResponseJson::GetJsonResponseArray(false, "Problem with passed in email. Please contact the trick-or-eat team."), HTTPCodes::SERVER_SERVICE_UNAVAILABLE);
		}

		if(empty($userInfo) || empty($userInfo['user_id']))
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "Email not registered."), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}
		if(!password_verify($app[Constants::PARAMETER_KEY]['password'], $userInfo['password']))
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, 'Incorrect password.'), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}
		$issuedAt = time();
		$expire = $issuedAt + self::VALID_TIME;
		$data = [
			'iat'  => $issuedAt,
			'exp'  => $expire,
			'data' => [
				// Data related to the signer user
				'userId'    => $userInfo['user_id'], // userid from the users table
				'email'     => $userInfo['email'], // User name
				'userRoles' => $userInfo['user_roles']
			]
		];
		$jwt = JWT::encode(
			$data,
			$app['jwt.key'],
			'HS512'
		);
		return $app->json(ResponseJson::GetJsonResponseArray(true, "", ["token" => ['jwt' => $jwt]]), HTTPCodes::SUCCESS_DATA_RETRIEVED);
	}



	/**
	 * Validates the passed in email
	 *
	 * @param String             $email
	 * @param Application $app
	 *
	 * @return bool returns true if the email is valid, false otherwise
	 */
	private function emailIsGood($email, Application $app)
	{
		/** @var  \Symfony\Component\Validator\Validator\ValidatorInterface $validator */
		$validator = $app['validator'];

		return $validator->validate($email, new Assert\Email())->count() === 0;
	}

	private function passwordIsGood($password)
	{
		//TODO: Modify the password input to rate the user's password strength. Any non-empty password should be accepted
		return !empty($password);
	}
}
