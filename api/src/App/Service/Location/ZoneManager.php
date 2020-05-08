<?php
declare(strict_types=1);

namespace TOE\App\Service\Location;


use TOE\App\Service\BaseDBService;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\HTTPCodes;
use TOE\GlobalCode\ResponseJson;

class ZoneManager extends BaseDBService
{
	/**
	 * Creates a new zone
	 *
	 * @param string $zoneName
	 * @param string $centralParkingAddress The address in which buses drop off and pick up participants
	 * @param string $centralBuildingName   The name of the main building that the bus picks up and drops off participants for easier directions
	 * @param int    $zoneRadiusMeter       The radius of the zone
	 * @param int    $housesCovered         The number of houses encompassed within the zone
	 * @param int    $zoom                  The zoom level that the map should load in
	 * @param string $latitude              The latitude of the central point of the zone
	 * @param string $longitude             The longitude of the central point of the zone
	 *
	 * @return array ['zone_id' => int, 'zone_name' => string]
	 * @throws LocationException
	 */
	public function createNewZone(string $zoneName, string $centralParkingAddress, string $centralBuildingName, int $zoneRadiusMeter, int $housesCovered, int $zoom, $latitude, $longitude)
	{
		try
		{
			$qb = $this->dbConn->createQueryBuilder();
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
				->setParameter(':zone_name', $zoneName, Constants::SILEX_PARAM_STRING)
				->setParameter(':status', "active", Constants::SILEX_PARAM_STRING)
				->setParameter(':parking', $centralParkingAddress, Constants::SILEX_PARAM_STRING)
				->setParameter(':building', $centralBuildingName, Constants::SILEX_PARAM_STRING)
				->setParameter(':radius', $zoneRadiusMeter, Constants::SILEX_PARAM_INT)
				->setParameter(':houses', $housesCovered, Constants::SILEX_PARAM_INT)
				->setParameter(':zoom', $zoom, Constants::SILEX_PARAM_INT)
				->setParameter(':latitude', $latitude, Constants::SILEX_PARAM_INT)
				->setParameter(':longitude', $longitude, Constants::SILEX_PARAM_INT);
			$inserted = $qb->execute();
			if($inserted < 1)
			{
				throw new \Exception("No rows modified when inserting the new zone: $zoneName");
			}
			$qb = $this->dbConn->createQueryBuilder();
			$qb->select('zone_id', 'zone_name')
				->from('zone')
				->where('zone_name = :zone_name')
				->setParameter(':zone_name', $zoneName, Constants::SILEX_PARAM_STRING);

			$newZone = $qb->execute()->fetch();
			if(empty($newZone))
			{
				throw new \Exception("Unable to fetch new zone from the database");
			}
			$newZone['zone_id'] = (int)$newZone['zone_id'];

			return $newZone;
		}
		catch(\Exception $ex)
		{
			throw new LocationException(get_class($ex) . ": " . $ex->getMessage());
		}
	}

	/**
	 * Checks if the passed in id is for an existing zone
	 *
	 * @param int $zoneId
	 *
	 * @return bool
	 */
	public function zoneExists(int $zoneId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('zone_id')
			->from('zone')
			->where('zone_id = :zoneId')
			->setParameter(':zoneId', $zoneId, Constants::SILEX_PARAM_INT);
		return !empty($qb->execute()->fetch());
	}

	/**
	 * Updates the values of the passed in zone id
	 *
	 * @param int    $zoneId
	 * @param string $zoneName
	 * @param string $centralParkingAddress
	 * @param string $centralBuildingName
	 * @param int    $zoneRadiusMeter
	 * @param int    $housesCovered
	 * @param int    $zoom
	 * @param        $latitude
	 * @param        $longitude
	 *
	 * @return bool true if there were any updates to be done on the record, false if no updates were made
	 */
	public function updateZone(int $zoneId, string $zoneName, string $centralParkingAddress, string $centralBuildingName, int $zoneRadiusMeter, int $housesCovered, int $zoom, $latitude, $longitude)
	{
		//update the zone
		$qb = $this->dbConn->createQueryBuilder();
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
			->setParameter(':zoneId', $zoneId, Constants::SILEX_PARAM_INT)
			->setParameter(':zone_name', $zoneName, Constants::SILEX_PARAM_STRING)
			->setParameter(':parking', $centralParkingAddress, Constants::SILEX_PARAM_STRING)
			->setParameter(':building', $centralBuildingName, Constants::SILEX_PARAM_STRING)
			->setParameter(':radius', $zoneRadiusMeter, Constants::SILEX_PARAM_INT)
			->setParameter(':houses', $housesCovered, Constants::SILEX_PARAM_INT)
			->setParameter(':zoom', $zoom, Constants::SILEX_PARAM_INT)
			->setParameter(':latitude', $latitude, Constants::SILEX_PARAM_DECIMAL)
			->setParameter(':longitude', $longitude, Constants::SILEX_PARAM_DECIMAL);
		return $qb->execute() === 1;
	}

	/**
	 * Checks if the passed in status is an acceptable one of zones
	 *
	 * @param string $status
	 *
	 * @return bool
	 */
	public function isStatusGood(string $status)
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

	/**
	 * Gets the zones of the passed in status and region
	 *
	 * @param int    $regionId
	 * @param string $status
	 *
	 * @return mixed[]
	 */
	public function getZones(int $regionId, string $status)
	{
		$qb = $this->dbConn->createQueryBuilder();
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
			->setParameter(':regionId', $regionId, Constants::SILEX_PARAM_INT);

		switch($status)
		{
			case 'working':
				$qb->andWhere("NOT status = 'retired'");
				break;
			case 'active':
			case 'inactive':
			case 'retired':
				$qb->andWhere('status = :status')
					->setParameter(':status', $status, Constants::SILEX_PARAM_STRING);
				break;
			case 'all':
			default:
		}

		$zones = $qb->execute()->fetchAll();

		foreach($zones as &$zone)
		{
			$zone['zone_id'] = (int)$zone['zone_id'];
			$zone['houses_covered'] = (int)$zone['houses_covered'];
		}
		return $zones;
	}

	/**
	 * Gets the zone of the passed in zone id
	 *
	 * @param int $zoneId
	 *
	 * @return array|mixed
	 */
	public function getZone(int $zoneId)
	{
		$qb = $this->dbConn->createQueryBuilder();
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
			->setParameter(':zoneId', $zoneId, Constants::SILEX_PARAM_INT);
		$zone = $qb->execute()->fetch();
		if (empty($zone))
		{
			return [];
		}

		$ints = ['zone_id', 'zone_radius_meter', 'houses_covered', 'zoom'];
		foreach($ints as $int)
		{
			$zone[$int] = (int)$zone[$int];
		}
		$zone['latitude'] = (float)$zone['latitude'];
		$zone['longitude'] = (float)$zone['longitude'];
		return $zone;
	}

	/**
	 * Updates the passed in zone with the passed in zone status
	 *
	 * @param int    $zoneId
	 * @param string $status
	 *
	 * @return bool true if the row was updated, false otherwise
	 */
	public function updateZoneStatus(int $zoneId, string $status)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->update('zone')
			->set('status', ':status')
			->where('zone_id = :zoneId')
			->setParameter(':status', $status)
			->setParameter(':zoneId', $zoneId);

		return $qb->execute() === 1;
	}
}