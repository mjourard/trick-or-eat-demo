<?php
declare(strict_types=1);

namespace TOE\App\Controller;

use Silex\Application;
use TOE\App\Service\Location\RegionManager;
use TOE\App\Service\User\UserLookupService;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\HTTPCodes;
use TOE\GlobalCode\ResponseJson;

class UserController extends BaseController
{
	/**
	 * Retrives the user info from the database
	 *
	 * @param \Silex\Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getUserInfo(Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ALL]);
		/** @var UserLookupService $userLookup */
		$userLookup = $app['user.lookup'];
		$results = $userLookup->getUserEntity($this->userInfo->getID(), $this->userInfo->toArray());

		return $app->json(ResponseJson::getJsonResponseArray(true, "", $results), HTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	/**
	 * A method to update the User table with information that is modifiable by the user (i.e. not by an administrator or something that might want to update a role)
	 *
	 *
	 * @param Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 * @throws \TOE\App\Service\User\UserException
	 */
	public function updateUserInfo(Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ALL]);
		$params = $app[Constants::PARAMETER_KEY];

		/** @var UserLookupService $userLookup */
		$userLookup = $app['user.lookup'];

		/** @var RegionManager $regionManager */
		$regionManager = $app['region'];

		if($params['region_id'] < 1)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "region_id must be a positive number"), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		//ensure there are changes to be made.
		$info = $userLookup->getUserInfo($this->userInfo->getEmail(), [
			'first_name',
			'last_name',
			'region_id'
		]);

		if(empty($info))
		{
			$this->logger->err("Authenticated email does not exist in the database", [
				'user_id' => $this->userInfo->getID(),
				'email'   => $this->userInfo->getEmail()
			]);

			return $app->json(ResponseJson::getJsonResponseArray(false, "Unable to find user entry for the authenticated user email"), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		if (!$regionManager->regionExists($params['region_id']))
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "Bad region id passed in"), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		if(
			$info['first_name'] === $params['first_name'] &&
			$info['last_name'] === $params['last_name'] &&
			$info['region_id'] === $params['region_id']
		)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "No changes detected"), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		try
		{
			$userLookup->updateBaseInfo($this->userInfo->getID(), $params['first_name'], $params['last_name'], $params['region_id']);
		}
		catch(\Exception $ex)
		{
			$this->logger->err("An error occurred while trying to update the base user info of a user", [
				'user_id'    => $this->userInfo->getID(),
				'first_name' => $params['first_name'],
				'last_name'  => $params['last_name'],
				'region_id'  => $params['region_id'],
				'err'        => $ex->getMessage()
			]);
			return $app->json(ResponseJson::getJsonResponseArray(false, "An error occurred when trying to update the user information."), HTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		}

		//retrieve the newly updated user object to be returned
		$results = $userLookup->getUserEntity($this->userInfo->getID(), $this->userInfo->toArray());

		return $app->json(ResponseJson::getJsonResponseArray(true, "", $results), HTTPCodes::SUCCESS_DATA_RETRIEVED);
	}
}
