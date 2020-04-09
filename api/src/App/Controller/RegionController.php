<?php
namespace TOE\App\Controller;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use TOE\GlobalCode\clsHTTPCodes;
use TOE\GlobalCode\clsResponseJson;

class RegionController extends BaseController
{
	public function getCountries(Request $request, Application $app)
	{
		$this->initializeInstance($app);
		$qb = $this->db->createQueryBuilder();
		$qb->select(
			'country_id',
			'country_name'
		)
			->from('country');

		$results = $qb->execute()->fetchAll();
		foreach ($results as &$country)
		{
			$country['country_id'] = (int)$country['country_id'];
		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ["countries" => $results]), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	public function getRegion(Request $request, Application $app, $countryId)
	{
		$this->initializeInstance($app);
		$qb = $this->db->createQueryBuilder();
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
		if (empty($results))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(true, "No regions for country with ID $countryId"), clsHTTPCodes::SUCCESS_NO_CONTENT);
		}

		/* ampersand (&) means 'by reference'
		 * needed so that we can modify array from inside loop
		 */
		foreach ($results as &$region)
		{
			$region['region_id'] = (int)$region['region_id'];
			$region['latitude'] = (float)$region['latitude'];
			$region['longitude'] = (float)$region['longitude'];
		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ['regions' => $results]), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}
}

?>
