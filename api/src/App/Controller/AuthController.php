<?php

namespace TOE\App\Controller;

use Silex\Application;
use \Firebase\JWT\JWT;
use Symfony\Component\Validator\Constraints as Assert;
use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsResponseJson;
use TOE\GlobalCode\clsHTTPCodes;

class AuthController extends BaseController
{
	const VALID_TIME = 18000;

	/**
	 * Registers a new user for the trick-or-eat application.
	 *
	 * @param \Silex\Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function register(Application $app)
	{
		if(!$this->EmailIsGood($app[clsConstants::PARAMETER_KEY]['email'], $app))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Bad email"), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		if(!$this->PasswordIsGood($app[clsConstants::PARAMETER_KEY]['password']))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Bad password"), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		$firstName = trim($app[clsConstants::PARAMETER_KEY]['first_name']);
		if(empty($firstName))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "First name cannot be empty."), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		$lastName = trim($app[clsConstants::PARAMETER_KEY]['last_name']);
		if(empty($lastName))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Last name cannot be empty."), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		$email = strtolower(trim($app[clsConstants::PARAMETER_KEY]['email']));

		$this->InitializeInstance($app);

		$userId = $app['user.lookup']->GetUserId($email);

		if($userId !== false)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "email of '$email' already registered"), clsHTTPCodes::CLI_ERR_CONFLICT);
		}

		//verify the region passed in exists
		if(!$this->RegionExists($app[clsConstants::PARAMETER_KEY]['region_id']))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "region_id of {$app[clsConstants::PARAMETER_KEY]['region_id']} is was not found in the database."), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		//insert user data into DB
		$qb = $this->db->createQueryBuilder();
		$qb->insert('user')
			->values([
				'email'      => ':email',
				'password'   => ':password',
				'first_name' => ':first_name',
				'last_name'  => ':last_name',
				'region_id'  => ':region_id'
			])
			->setParameter(':email', $email)
			->setParameter(':password', password_hash($app[clsConstants::PARAMETER_KEY]['password'], PASSWORD_DEFAULT))
			->setParameter(':first_name', $firstName)
			->setParameter(':last_name', $lastName)
			->setParameter(':region_id', $app[clsConstants::PARAMETER_KEY]['region_id']);

		if(!$qb->execute() === 0)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, 'There was a problem registering the user'), clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		};

		$userId = $app['user.lookup']->GetUserId($email);

		$qb = $this->db->createQueryBuilder();
		$qb->insert('user_role')
			->values([
				'user_id' => $userId,
				'role'    => ':role'
			])
			->setParameter(':role', clsConstants::ROLE_PARTICIPANT, clsConstants::SILEX_PARAM_STRING);

		if(!$qb->execute() === 0)
		{
			//TODO: delete the newly created user so they can attempt to sign up again
			return $app->json(clsResponseJson::GetJsonResponseArray(false, 'There was a problem registering the user\'s role.'), clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, ""), clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
	}

	/**
	 * Takes in an email and password from a request and returns a response containing either a login token or an error message
	 *
	 * @param \Silex\Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function login(Application $app)
	{
		$this->InitializeInstance($app);
		$qb = $this->db->createQueryBuilder();

		$delim = ",";
		$qb->select(
			'u.user_id',
			'email',
			'password',
			"GROUP_CONCAT(ur.role SEPARATOR '$delim') as user_roles"
		)
			->from('user', 'u')
			->leftJoin('u', 'user_role', 'ur', 'u.user_id = ur.user_id')
			->where('email = :email')
			->setParameter(':email', strtolower($app[clsConstants::PARAMETER_KEY]["email"]));

		$userInfo = $qb->execute()->fetchAll();
		if(count($userInfo) > 1)
		{
			$app->json(clsResponseJson::GetJsonResponseArray(false, "Database inconsistency; more than one user matches these login details."), clsHTTPCodes::SERVER_GENERIC_ERROR);
		};

		if(empty($userInfo))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Email not registered."), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		};
		$userInfo = $userInfo[0];
		if(!password_verify($app[clsConstants::PARAMETER_KEY]['password'], $userInfo['password']))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, 'Incorrect password.'), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		};
		$issuedAt = time();
		$expire = $issuedAt + self::VALID_TIME;
		$data = [
			'iat'  => $issuedAt,
			'exp'  => $expire,
			'data' => [
				// Data related to the signer user
				'userId'    => $userInfo['user_id'], // userid from the users table
				'email'     => $userInfo['email'], // User name
				'userRoles' => explode($delim, $userInfo['user_roles'])
			]
		];
		$jwt = JWT::encode(
			$data,
			$app['jwt.key'],
			'HS512'
		);
		$token = ['jwt' => $jwt];

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ["token" => $token]), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	private function RegionExists($regionId)
	{
		$qb = $this->db->createQueryBuilder();

		$qb->select('region_id');
		$qb->from('region');
		$qb->where('region_id = :region_id');
		$qb->setParameter('region_id', $regionId, clsConstants::SILEX_PARAM_INT);

		$results = $qb->execute();

		return !empty($results->fetchAll());
	}

	/**
	 * Validates the passed in email
	 *
	 * @param String             $email
	 * @param \Silex\Application $app
	 *
	 * @return bool returns true if the email is valid, false otherwise
	 */
	private function EmailIsGood($email, Application $app)
	{
		/** @var  \Symfony\Component\Validator\Validator\ValidatorInterface $validator */
		$validator = $app['validator'];

		return $validator->validate($email, new Assert\Email())->count() === 0;
	}

	private function PasswordIsGood($password)
	{
		//TODO: Modify the password input to rate the user's password strength. Any non-empty password should be accepted
		return !empty($password);
	}
}
