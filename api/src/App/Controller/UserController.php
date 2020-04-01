<?php
namespace TOE\App\Controller;

use Silex\Application;
use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsHTTPCodes;
use TOE\GlobalCode\clsResponseJson;

class UserController extends BaseController
{
	/**
	 * Retrives the user info from the database
	 *
	 * @param \Silex\Application                        $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getUserInfo(Application $app)
	{
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ALL]);
		$results = $this->GetUserEntity();

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", $results), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	/**
	 * A method to update the User table with information that is modifiable by the user (i.e. not by an administrator or something that might want to update a role)
	 *
	 *
	 * @param \Silex\Application                        $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function updateUserInfo(Application $app)
	{
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ALL]);
		$params = $app[clsConstants::PARAMETER_KEY];
		$qb = $this->db->createQueryBuilder();

		if ($params['region_id'] < 1)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "region_id must be a positive number"), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		//ensure there are changes to be made.
		$qb->select('user_id')
			->from('user')
			->where('user_id = user_id')
			->andWhere('first_name = :first_name')
			->andWhere('last_name = :last_name')
			->andWhere('region_id = :region_id')
			->setParameter(':first_name', $params['first_name'], clsConstants::SILEX_PARAM_STRING)
			->setParameter(':last_name', $params['last_name'], clsConstants::SILEX_PARAM_STRING)
			->setParameter(':region_id', $params['region_id']);

		if (count($qb->execute()->fetchAll()) > 0)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "No changes detected"), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}



		$qb = $this->db->createQueryBuilder();
		$qb->update('user')
			->set('first_name', ':first_name')
			->set('last_name', ':last_name')
			->set('region_id', ':region_id')
			->where('user_id = :user_id')
			->setParameter(':first_name', $params['first_name'], clsConstants::SILEX_PARAM_STRING)
			->setParameter(':last_name', $params['last_name'], clsConstants::SILEX_PARAM_STRING)
			->setParameter(':region_id', $params['region_id'], clsConstants::SILEX_PARAM_STRING)
			->setParameter(':user_id', $this->userInfo->getID());

		try
		{
			$qb->execute();
		}
		catch (\Exception $ex)
		{
			if (stripos($ex->getMessage(), 'foreign key constraint fails') && stripos($ex->getMessage(), 'region_id'))
			{
				return $app->json(clsResponseJson::GetJsonResponseArray(false, "An error occurred when trying to update the user information. Bad region Id used of: " . $params['region_id']), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
			}
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "An error occurred when trying to update the user information."), clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		}

		//retrieve the newly updated user object to be returned
		$results = $this->GetUserEntity();

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", $results), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	/**
	 * Gets a user entity object. For controller use only.
	 *
	 * @return array|null
	 */
	private function GetUserEntity()
	{
		if (!isset($this->db))
		{
			return null;
		}

		$qb = $this->db->createQueryBuilder();

		$qb->select(
			'u.first_name',
			'u.last_name',
			'u.region_id',
			'r.region_name',
			'c.country_id',
			'c.country_name',
			'e.event_id',
			'e.event_name',
			't.team_id',
			't.name as team_name'
		)
			->from('user', 'u')
			->leftJoin('u', 'member', 'm', 'u.user_id = m.user_id')
			->leftJoin('m', 'event', 'e', 'm.event_id = e.event_id')
			->leftJoin('m', 'team', 't', 'm.team_id = t.team_id')
			->leftJoin('u', 'region', 'r', 'u.region_id = r.region_id')
			->leftJoin('r', 'country', 'c', 'r.country_id = c.country_id')
			->where("u.user_id = {$this->userInfo->getID()}");

		$results = $qb->execute()->fetch();

		//TODO: visit if we want to be removing old links for users to events. If not, possibly store past event info in a different table
		//TODO: also, if a user is signed up for multiple events (i.e. really committed members or event organizers) this will fail...

		$results['region_id'] = (int)$results['region_id'];
		$results['country_id'] = (int)$results['country_id'];
		$results['event_id'] = $results['event_id'] === null ? $results['event_id'] : (int)$results['event_id'];
		$results['team_id'] = $results['team_id'] === null ? $results['team_id'] : (int)$results['team_id'];
		$results = array_merge($results, $this->userInfo->toArray());

		return $results;
	}
}
