<?php
declare(strict_types=1);

namespace TOE\App\Controller;


use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use TOE\App\Service\SiteHealth\InfrastructureManager;
use TOE\GlobalCode\HTTPCodes;
use TOE\GlobalCode\ResponseJson;

class SiteHealthController extends BaseController
{
	/**
	 * Gets a list of site issues that can be detected by the application layer and returns them to the user
	 *
	 * @param Request     $request
	 * @param Application $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getSiteIssues(Request $request, Application $app)
	{
		$this->initializeInstance($app);
		//check if the serverless database is ready
		/** @var InfrastructureManager $infra */
		$infra = $app['infrastructure'];

		if (!$infra->isDbReady($this->logger))
		{
			return $app->json(ResponseJson::getJsonResponseArray(true, "Issues with trick-or-eat detected", [
				'lvl' => 'Warning',
				'hover' => 'Database initializing...',
				'message' => $this->getStatusMessage('Database is initializing. Initialization usually takes about 15 seconds. Once the notification icon clears, refresh the page for full functionality.')

			]), HTTPCodes::SUCCESS_DATA_RETRIEVED);
		}

		//no issues
		return $app->json(ResponseJson::getJsonResponseArray(true, ''), HTTPCodes::SUCCESS_NO_CONTENT);
	}

	/**
	 * Creates a status message with a timestamp that can be shown to the user
	 *
	 * @param string $msg
	 *
	 * @return string
	 */
	public function getStatusMessage(string $msg)
	{
		$timezone = 'UTC';
		try
		{
			$now = new \DateTime('now', new \DateTimeZone($timezone));
			$dt = $now->format('M/d H:i:s');
		}
		catch(\Exception $ex)
		{
			$this->logger->err("Unable to initlaize DateTime object", [
				'msg' => $ex->getMessage()
			]);
			$dt = "N/A";
		}

		return sprintf("%s %s: %s", $dt, $timezone, $msg);
	}
}