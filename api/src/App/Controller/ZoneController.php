<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 1/30/2017
 * Time: 12:50 PM
 */

namespace TOE\App\Controller;

use Silex\Application;
use TOE\App\Service\Location\RegionManager;
use TOE\App\Service\Location\ZoneManager;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\HTTPCodes;
use TOE\GlobalCode\ResponseJson;

class ZoneController extends BaseController
{
	public const MAX_ZOOM = 20;
	public const MIN_ZOOM = 1;

	public function createZone(Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER]);
		$params = $app[Constants::PARAMETER_KEY];
		$response = $this->verifyParams($app, $params);
		if($response !== null)
		{
			return $response;
		}

		/** @var ZoneManager $zoneManager */
		$zoneManager = $app['zone'];

		try
		{
			$newZone = $zoneManager->createNewZone(
				$params['zone_name'],
				$params['central_parking_address'],
				$params['central_building_name'],
				$params['zone_radius_meter'],
				$params['houses_covered'],
				$params['zoom'],
				$params['latitude'],
				$params['longitude']
			);

			return $app->json(ResponseJson::GetJsonResponseArray(true, "", ['zone' => $newZone]), HTTPCodes::SUCCESS_RESOURCE_CREATED);
		}
		catch(\Exception $e)
		{
			$this->logger->err("Error while trying to create a new zone", [
				'attempted_name'          => $params['zone_name'],
				'central_parking_address' => $params['central_parking_address'],
				'err'                     => $e->getMessage()
			]);

			return $app->json(ResponseJson::GetJsonResponseArray(false, "There was an error creating a the new zone: {$e->getMessage()}"), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}
	}

	public function editZone(Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER]);
		$params = $app[Constants::PARAMETER_KEY];
		$response = $this->verifyParams($app, $params);
		if($response !== null)
		{
			return $response;
		}

		/** @var ZoneManager $zoneManager */
		$zoneManager = $app['zone'];

		if(!$zoneManager->zoneExists($params['zone_id']))
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "Zone with ID of {$params['zone_id']} does not exist."), HTTPCodes::CLI_ERR_NOT_FOUND);
		}

		try
		{
			$zoneManager->updateZone(
				$params['zone_id'],
				$params['zone_name'],
				$params['central_parking_address'],
				$params['central_building_name'],
				$params['zone_radius_meter'],
				$params['houses_covered'],
				$params['zoom'],
				$params['latitude'],
				$params['longitude']
			);
			return $app->json(ResponseJson::GetJsonResponseArray(true, ""), HTTPCodes::SUCCESS_DATA_RETRIEVED);
		}
		catch(\Exception $e)
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "There was an error updating the zone: {$e->getMessage()}"), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}
	}

	/**
	 *
	 * @param Application $app
	 * @param Integer            $regionId The region that you are retrieving the zones for.
	 * @param String             $status   The statuses of zones to be returned. If passing in 'all', will return all zones. If passing in 'working', will return all non-retired zones.
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getZones(Application $app, $regionId, $status)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER]);

		/** @var RegionManager $regionManager */
		$regionManager = $app['region'];
		/** @var ZoneManager $zoneManager */
		$zoneManager = $app['zone'];

		if(!$zoneManager->isStatusGood($status) && $status !== 'all' && $status !== 'working')
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "Bad status passed in '$status'."), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}


		if(!$regionManager->regionExists($regionId))
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "Bad region Id passed in '$regionId'"), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		return $app->json(ResponseJson::GetJsonResponseArray(true, "", ['zones' => $zoneManager->getZones($regionId, $status)]), HTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	public function getZone(Application $app, $zoneId)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER]);

		/** @var ZoneManager $zoneManager */
		$zoneManager = $app['zone'];

		$zone = $zoneManager->getZone($zoneId);

		if(empty($zone))
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "Zone with id $zoneId does not exist."), HTTPCodes::CLI_ERR_NOT_FOUND);
		}
		return $app->json(ResponseJson::GetJsonResponseArray(true, "", ['zone' => $zone]), HTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	public function setZoneStatus(Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER]);
		$status = $app[Constants::PARAMETER_KEY]['status'];
		$zoneId = $app[Constants::PARAMETER_KEY]['zone_id'];

		/** @var ZoneManager $zoneManager */
		$zoneManager = $app['zone'];

		if(!$zoneManager->isStatusGood($status))
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "Bad status passed in: $status"), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		if(!$zoneManager->zoneExists($zoneId))
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "Zone with id $zoneId does not exist."), HTTPCodes::CLI_ERR_NOT_FOUND);
		}

		$zoneManager->updateZoneStatus($zoneId, $status);
		return $app->json(ResponseJson::GetJsonResponseArray(true, ""), HTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	/**
	 * Verifies the common params of zones
	 *
	 * @param Application $app
	 * @param             $params
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse|null
	 */
	private function verifyParams(Application $app, $params)
	{
		if($params['houses_covered'] < 1)
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "param houses_covered must be a positive number. Newly created zones must cover at least 1 house."), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}
		if($params['zoom'] < self::MIN_ZOOM || $params['zoom'] > self::MAX_ZOOM)
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, sprintf("param zoom must be between %d and %d", self::MIN_ZOOM, self::MAX_ZOOM)), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		return null;
	}

}