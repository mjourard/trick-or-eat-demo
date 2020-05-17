<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 2/27/2017
 * Time: 5:57 PM
 */

namespace TOE\App\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use TOE\App\Service\Location\ZoneManager;
use TOE\App\Service\Route\Archive\iObjectStorage;
use TOE\App\Service\Route\Archive\Route;
use TOE\App\Service\Route\Archive\RouteManagementException;
use TOE\App\Service\Route\Archive\RouteManager;
use TOE\App\Service\Route\Assignment\AssignmentManager;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\HTTPCodes;
use TOE\GlobalCode\ResponseJson;

class RouteArchiveController extends BaseController
{
	/**
	 * @var iObjectStorage
	 */
	private $objectStorage;
	/**
	 * @var RouteManager
	 */
	private $routeManager;

	/**
	 * Adds a route to the database. Passes the kmz file to wherever we are hosting our routes
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Silex\Application                        $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function addRoute(Request $request, Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER]);

		/* @var $file \Symfony\Component\HttpFoundation\File\UploadedFile */
		if(empty($request->files) || ($file = $request->files->get("file")) === null)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "No files received to upload."), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		/** @var ZoneManager $zoneManager */
		$zoneManager = $this->app['zone'];

		$zoneId = (int)$app[Constants::PARAMETER_KEY]['zone_id'];
		$zoneData = $zoneManager->getZone($zoneId);
		if (empty($zoneData))
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "Zone with passed in ID does not exist"), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		/** @var AssignmentManager $assignmentManager */
		$assignmentManager = $this->app['route.assignment'];
		if (!in_array($app[Constants::PARAMETER_KEY]['type'], $assignmentManager->getRouteTypes()))
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "Passed in route type not an acceptable route type"), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		$route = Route::init($file, $app[Constants::PARAMETER_KEY]['zone_id'], $this->userInfo->getID());
		$route->type = $app[Constants::PARAMETER_KEY]['type'];
		$route->wheelchairAccessible = $app[Constants::PARAMETER_KEY]['mobility'];
		$route->blindAccessible = $app[Constants::PARAMETER_KEY]['visual'];
		$route->hearingAccessible = $app[Constants::PARAMETER_KEY]['hearing'];
		$route->requiredPeople = Constants::MAX_ROUTE_MEMBERS;

		if($this->routeManager->getExistingRouteId($route) !== false)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "You already have a route '{$route->routeName}' in zone '{$zoneData['zone_name']}'"), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		try
		{
			$route = $this->objectStorage->saveRouteFile($file, $route);
			$route = $this->routeManager->saveRouteInfo($route);
			return $app->json(ResponseJson::getJsonResponseArray(true, "Upload Successful", ['route_id' => $route->getRouteId()]));
		}
		catch(RouteManagementException $e)
		{
			$this->logger->error($e->getMessage());
			return $app->json(ResponseJson::getJsonResponseArray(false, $e->getMessage()), 400);
		}
	}

	/**
	 * Deletes a route from the database and from where we are hosting the kmz files
	 *
	 * @param Application $app
	 *
	 * @param int                $zoneId  The id of the zone you of the route you want to delete
	 * @param int                $routeId The id of the route you want to delete
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function deleteRoute(Application $app, $zoneId, $routeId)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER]);
		$route = $this->routeManager->getRouteInfo($routeId);

		if($route === null)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "No route found with Zone ID of $zoneId and route id of $routeId"));
		}

		$this->routeManager->retireRoute($routeId);
		$this->objectStorage->deleteRouteFile($route);

		return $app->json(ResponseJson::getJsonResponseArray(true, "Route {$route->routeName} removed"));
	}

	/**
	 * Returns a list of route_archive entities that are needed to display the route information.
	 *
	 * @param Application $app
	 * @param int                $zoneId
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getRoutes(Application $app, $zoneId)
	{

		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER]);
		$routes = $this->routeManager->getRoutesInZone($zoneId);
		return $app->json(ResponseJson::getJsonResponseArray(true, "", ["routes" => $routes]));
	}

	/**
	 * Initializes the controller with proper typed properties
	 *
	 * @param Application $app
	 */
	protected function initializeInstance(Application $app)
	{
		parent::initializeInstance($app);
		$this->objectStorage = $app['route.object_storage'];
		$this->routeManager = $app['route.manager'];
	}
}