<?php
declare(strict_types=1);

namespace TOE\App\Service\Location;


use TOE\App\Service\BaseDBService;
use TOE\GlobalCode\Constants;

class RegionManager extends BaseDBService
{
	/**
	 * Checks if the passed in region id exists in the database
	 *
	 * @param int $regionId
	 *
	 * @return bool
	 */
	public function regionExists($regionId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('region_id')
			->from('region')
			->where('region_id = :region_id')
			->setParameter('region_id', $regionId, Constants::SILEX_PARAM_INT);
		$results = $qb->execute();

		return !empty($results->fetchAll());
	}

	/**
	 * Gets the countries saved in the database
	 *
	 * @return array
	 */
	public function getAllCountries()
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			'country_id',
			'country_name'
		)
			->from('country');
		$results = $qb->execute()->fetchAll();
		foreach($results as &$country)
		{
			$country['country_id'] = (int)$country['country_id'];
		}

		return $results;
	}

	/**
	 * Gets the regions of the passed in country id
	 *
	 * @param $countryId
	 *
	 * @return array
	 */
	public function getRegionsOfCountry($countryId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			'region_id',
			'region_name',
			'latitude',
			'longitude'
		)
			->from('region')
			->where('country_id = :cid')
			->setParameter(':cid', $countryId);
		$results = $qb->execute()->fetchAll();
		if(empty($results))
		{
			return [];
		}

		foreach($results as &$region)
		{
			$region['region_id'] = (int)$region['region_id'];
			$region['latitude'] = (float)$region['latitude'];
			$region['longitude'] = (float)$region['longitude'];
		}

		return $results;
	}
}