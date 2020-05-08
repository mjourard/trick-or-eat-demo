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
use TOE\App\Service\Route\iObjectStorage;
use TOE\App\Service\Route\Route;
use TOE\App\Service\Route\RouteManagementException;
use TOE\App\Service\Route\RouteManager;
use TOE\GlobalCode\Constants;
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

		if(empty($request->files))
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "No files received to upload."));
		}

		/* @var $file \Symfony\Component\HttpFoundation\File\UploadedFile */
		$file = $request->files->get("file");
		$route = Route::init($file, $app[Constants::PARAMETER_KEY]['zone_id'], $this->userInfo->getID());
		$route->type = $app[Constants::PARAMETER_KEY]['type'];
		$route->wheelchairAccessible = $app[Constants::PARAMETER_KEY]['mobility'];
		$route->blindAccessible = $app[Constants::PARAMETER_KEY]['visual'];
		$route->hearingAccessible = $app[Constants::PARAMETER_KEY]['hearing'];
		$route->requiredPeople = Constants::MAX_ROUTE_MEMBERS;

		if($this->routeManager->getExistingRouteId($route) !== false)
		{
			return $app->json(ResponseJson::GetJsonResponseArray(false, "You already have a route '{$route->routeName}' in zone {$app[Constants::PARAMETER_KEY]['zone_id']}"));
		}

		try
		{
			$route = $this->objectStorage->saveRouteFile($file, $route);
			$route = $this->routeManager->saveRouteInfo($route);
			return $app->json(ResponseJson::GetJsonResponseArray(true, "Upload Successful", ['route_id' => $route->routeId]));
		}
		catch(RouteManagementException $e)
		{
			$this->logger->error($e->getMessage());
			return $app->json(ResponseJson::GetJsonResponseArray(false, $e->getMessage()), 400);
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
			return $app->json(ResponseJson::GetJsonResponseArray(false, "No route found with Zone ID of $zoneId and route id of $routeId"));
		}

		$this->routeManager->retireRoute($routeId);
		$this->objectStorage->deleteRouteFile($route);

		return $app->json(ResponseJson::GetJsonResponseArray(true, "Route {$route->routeName} removed"));
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
		return $app->json(ResponseJson::GetJsonResponseArray(true, "", ["routes" => $routes]));
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