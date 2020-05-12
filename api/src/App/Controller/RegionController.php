<?php
declare(strict_types=1);

namespace TOE\App\Controller;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use TOE\App\Service\Location\RegionManager;
use TOE\GlobalCode\HTTPCodes;
use TOE\GlobalCode\ResponseJson;

class RegionController extends BaseController
{
	public function getCountries(Request $request, Application $app)
	{
		$this->initializeInstance($app);
		/** @var RegionManager $regionManager */
		$regionManager = $app['region'];

		return $app->json(ResponseJson::getJsonResponseArray(true, "", ["countries" => $regionManager->getAllCountries()]), HTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	public function getRegion(Request $request, Application $app, $countryId)
	{
		$this->initializeInstance($app);
		/** @var RegionManager $regionManager */
		$regionManager = $app['region'];

		$regions = $regionManager->getRegionsOfCountry($countryId);
		if (empty($regions))
		{
			return $app->json(ResponseJson::getJsonResponseArray(true, "No regions for country with ID $countryId"), HTTPCodes::SUCCESS_NO_CONTENT);
		}

		return $app->json(ResponseJson::getJsonResponseArray(true, "", ['regions' => $regions]), HTTPCodes::SUCCESS_DATA_RETRIEVED);
	}
}
