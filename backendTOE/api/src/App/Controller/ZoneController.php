<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 1/30/2017
 * Time: 12:50 PM
 */

namespace TOE\App\Controller;

use Silex\Application;
use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsHTTPCodes;
use TOE\GlobalCode\clsResponseJson;

class ZoneController extends BaseController
{
	const MAX_ZOOM = 20;
	const MIN_ZOOM = 1;

	public function createZone(Application $app)
	{
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER]);
		$params = $app[clsConstants::PARAMETER_KEY];
		$response = $this->verifyParams($app, $params);
		if($response !== null)
		{
			return $response;
		}


		$qb = $this->db->createQueryBuilder();
		$qb->insert('zone')
			->values([
				'zone_name'               => ':zone_name',
				'status'                  => ':status',
				'central_parking_address' => ':parking',
				'central_building_name'   => ':building',
				'zone_radius_meter'       => ':radius',
				'houses_covered'          => ':houses',
				'zoom'                    => ':zoom',
				'latitude'                => ':latitude',
				'longitude'               => ':longitude',
				'region_id'               => 9
				//TODO: change this to detect what region the zone is in based on latitude and longitude
			])
			->setParameter(':zone_name', $params['zone_name'], clsConstants::SILEX_PARAM_STRING)
			->setParameter(':status', "active", clsConstants::SILEX_PARAM_STRING)
			->setParameter(':parking', $params['central_parking_address'], clsConstants::SILEX_PARAM_STRING)
			->setParameter(':building', $params['central_building_name'], clsConstants::SILEX_PARAM_STRING)
			->setParameter(':radius', $params['zone_radius_meter'], clsConstants::SILEX_PARAM_INT)
			->setParameter(':houses', $params['houses_covered'], clsConstants::SILEX_PARAM_INT)
			->setParameter(':zoom', $params['zoom'], clsConstants::SILEX_PARAM_INT)
			->setParameter(':latitude', $params['latitude'], clsConstants::SILEX_PARAM_INT)
			->setParameter(':longitude', $params['longitude'], clsConstants::SILEX_PARAM_INT);

		try
		{
			$qb->execute();
		}
		catch(\Exception $e)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "There was an error creating a the new zone: {$e->getMessage()}"), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		//verify the new zone was created
		$qb = $this->db->createQueryBuilder();
		$qb->select('zone_id', 'zone_name')
			->from('zone')
			->where('zone_name = :zone_name')
			->setParameter(':zone_name', $params['zone_name'], clsConstants::SILEX_PARAM_STRING);

		$newZone = $qb->execute()->fetchAll();

		if(empty($newZone))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "There was an error fetching the new zone"), clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ['zone' => $newZone[0]]), clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
	}

	public function editZone(Application $app)
	{
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER]);
		$params = $app[clsConstants::PARAMETER_KEY];
		$response = $this->verifyParams($app, $params);
		if($response !== null)
		{
			return $response;
		}

		//varify the zone exists
		$qb = $this->db->createQueryBuilder();
		$qb->select('zone_id')
			->from('zone')
			->where('zone_id = :zoneId')
			->setParameter(':zoneId', $params['zone_id'], clsConstants::SILEX_PARAM_INT);

		if(empty($qb->execute()->fetchAll()))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Zone with ID of {$params['zone_id']} does not exist."), clsHTTPCodes::CLI_ERR_NOT_FOUND);
		}

		//update the zone
		$qb = $this->db->createQueryBuilder();
		$qb->update('zone')
			->set('zone_name', ':zone_name')
			->set('date_modified', 'utc_timestamp()')
			->set('central_parking_address', ':parking')
			->set('central_building_name', ':building')
			->set('zone_radius_meter', ':radius')
			->set('houses_covered', ':houses')
			->set('zoom', ':zoom')
			->set('latitude', ':latitude')
			->set('longitude', ':longitude')
			->where('zone_id = :zoneId')
			->setParameter(':zoneId', $params['zone_id'], clsConstants::SILEX_PARAM_INT)
			->setParameter(':zone_name', $params['zone_name'], clsConstants::SILEX_PARAM_STRING)
			->setParameter(':parking', $params['central_parking_address'], clsConstants::SILEX_PARAM_STRING)
			->setParameter(':building', $params['central_building_name'], clsConstants::SILEX_PARAM_STRING)
			->setParameter(':radius', $params['zone_radius_meter'], clsConstants::SILEX_PARAM_INT)
			->setParameter(':houses', $params['houses_covered'], clsConstants::SILEX_PARAM_INT)
			->setParameter(':zoom', $params['zoom'], clsConstants::SILEX_PARAM_INT)
			->setParameter(':latitude', $params['latitude'], clsConstants::SILEX_PARAM_DECIMAL)
			->setParameter(':longitude', $params['longitude'], clsConstants::SILEX_PARAM_DECIMAL);

		try
		{
			$qb->execute();
		}
		catch(\Exception $e)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "There was an error updating the zone: {$e->getMessage()}"), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, ""), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	/**
	 *
	 * @param \Silex\Application $app
	 * @param Integer            $regionId The region that you are retrieving the zones for.
	 * @param String             $status   The statuses of zones to be returned. If passing in 'all', will return all zones. If passing in 'working', will return all non-retired zones.
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getZones(Application $app, $regionId, $status)
	{
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER]);

		if(!$this->isStatusGood($status) && $status !== 'all' && $status !== 'working')
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Bad status passed in '$status'."), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		if(!$this->regionExists($regionId))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Bad region Id passed in '$regionId'"), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		$qb = $this->db->createQueryBuilder();
		$qb->select(
			'zone_id',
			'zone_name',
			'status',
			'date_added',
			'date_modified',
			'central_parking_address',
			'central_building_name',
			'zone_radius_meter',
			'houses_covered'
		)
			->from('zone')
			->where('region_id = :regionId')
			->setParameter(':regionId', $regionId, clsConstants::SILEX_PARAM_INT);

		switch($status)
		{
			case 'working':
				$qb->andWhere("NOT status = 'retired'");
				break;
			case 'active':
			case 'inactive':
			case 'retired':
				$qb->andWhere('status = :status')
					->setParameter(':status', $status, clsConstants::SILEX_PARAM_STRING);
				break;
			case 'all':
			default:
		}

		$zones = $qb->execute()->fetchAll();

		foreach($zones as &$zone)
		{
			$zone['zone_id'] = (int)$zone['zone_id'];
		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ['zones' => $zones]), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	public function getZone(Application $app, $zoneId)
	{
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER]);
		$qb = $this->db->createQueryBuilder();
		$qb->select(
			'zone_id',
			'zone_name',
			'status',
			'date_added',
			'date_modified',
			'central_parking_address',
			'central_building_name',
			'zone_radius_meter',
			'houses_covered',
			'latitude',
			'longitude',
			'zoom'
		)
			->from('zone')
			->where('zone_id = :zoneId')
			->setParameter(':zoneId', $zoneId, clsConstants::SILEX_PARAM_INT);

		$zone = $qb->execute()->fetchAll();

		if(empty($zone))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Zone with id $zoneId does not exist."), clsHTTPCodes::CLI_ERR_NOT_FOUND);
		}

		$ints = ['zone_id', 'zone_radius_meter', 'houses_covered', 'zoom'];
		foreach($ints as $int)
		{
			$zone[0][$int] = (int)$zone[0][$int];
		}
		$zone[0]['latitude'] = (float)$zone[0]['latitude'];
		$zone[0]['longitude'] = (float)$zone[0]['longitude'];

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ['zone' => $zone[0]]), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	public function setZoneStatus(Application $app)
	{
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER]);
		$status = $app[clsConstants::PARAMETER_KEY]['status'];
		$zoneId = $app[clsConstants::PARAMETER_KEY]['zone_id'];

		if(!$this->isStatusGood($status))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Bad status passed in: $status"), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		$qb = $this->db->createQueryBuilder();

		$qb->select('zone_id')
			->from('zone')
			->where('zone_id = :zoneId')
			->setParameter(':zoneId', $zoneId);

		if(empty($qb->execute()->fetchAll()))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Zone with id $zoneId does not exist."), clsHTTPCodes::CLI_ERR_NOT_FOUND);
		}

		$qb->update('zone')
			->set('status', ':status')
			->where('zone_id = :zoneId')
			->setParameter(':status', $status)
			->setParameter(':zoneId', $zoneId);

		$qb->execute();

		return $app->json(clsResponseJson::GetJsonResponseArray(true, ""), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	private function isStatusGood($status)
	{
		switch($status)
		{
			case 'active':
			case 'inactive':
			case 'retired':
				return true;
			default:
				return false;
		}
	}

	private function regionExists($regionId)
	{
		if(!is_int($regionId))
		{
			return false;
		}

		$qb = $this->db->createQueryBuilder();

		$qb->select('region_id')
			->from('region')
			->where('region_id = :region_id')
			->setParameter('region_id', $regionId, clsConstants::SILEX_PARAM_INT);

		return !empty($qb->execute()->fetchAll());
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
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "param houses_covered must be a positive number. Newly created zones must cover at least 1 house."), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}
		if($params['zoom'] < self::MIN_ZOOM || $params['zoom'] > self::MAX_ZOOM)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, sprintf("param zoom must be between %d and %d", self::MIN_ZOOM, self::MAX_ZOOM)), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		return null;
	}

}