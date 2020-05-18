<?php
declare(strict_types=1);


namespace TOE\App\Service\Route\Archive;


use Doctrine\DBAL\Query\QueryBuilder;
use TOE\App\Service\BaseDBService;
use TOE\GlobalCode\Constants;

class RouteManager extends BaseDBService
{
	/**
	 * Saves the route data so that it can be retrieved later with getRouteInfo
	 *
	 * @param Route $route
	 *
	 * @return Route The route object with updated properties
	 * @throws RouteManagementException
	 */
	public function saveRouteInfo(Route $route)
	{
		if(($routeId = $this->getExistingRouteId($route)) !== false)
		{
			throw new RouteManagementException("Updating existing routes not yet implemented");
		}
		$qb = $this->dbConn->createQueryBuilder();
		$qb->insert('route_archive')
			->values([
				'route_file_url'        => ':route_file_url',
				'route_name'            => ':name',
				'required_people'       => ':required_people',
				'type'                  => ':type',
				'wheelchair_accessible' => ':mobile',
				'blind_accessible'      => ':visual',
				'hearing_accessible'    => ':hearing',
				'zone_id'               => ':zone_id',
				'owner_user_id'         => ':owner_user_id'
			])
			->setParameter(':route_file_url', $route->routeFilePath)
			->setParameter(':name', $route->routeName)
			->setParameter(':required_people', $route->requiredPeople)
			->setParameter(':type', $route->type)
			->setParameter(':mobile', $route->wheelchairAccessible)
			->setParameter(':visual', $route->blindAccessible)
			->setParameter(':hearing', $route->hearingAccessible)
			->setParameter(':zone_id', $route->zoneId)
			->setParameter(':owner_user_id', $route->ownerUserId);
		if($qb->execute() > 0)
		{
			$route->setRouteId($qb->getConnection()->lastInsertId());
		}

		return $route;
	}

	/**
	 * Deletes the route file of the passed in route id
	 *
	 * @param int $id The id of the file to delete
	 *
	 * @return bool true if the route is successfully retired, false otherwise
	 */
	public function retireRoute(int $id)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->delete('route_archive')
			->where('route_id = :routeId')
			->setParameter(':routeId', $id, Constants::SILEX_PARAM_INT);

		return $qb->execute() > 0;
	}

	/**
	 * Retrieves the route of the passed in route id
	 *
	 * @param int $id The id of the route to retrieve
	 *
	 * @return Route
	 */
	public function getRouteInfo(int $id)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select([
			'route_id',
			'route_file_url',
			'route_name',
			'required_people',
			'type',
			'wheelchair_accessible',
			'blind_accessible',
			'hearing_accessible',
			'zone_id',
			'owner_user_id'
		])
			->from('route_archive')
			->where('route_id = :route_id')
			->setParameter(':route_id', $id, Constants::SILEX_PARAM_INT);
		$rows = $qb->execute()->fetchAll();
		if(empty($rows))
		{
			return null;
		}

		return new Route($rows[0]);
	}

	/**
	 * Checks if the passed in arguments are mapped to an existing route file in the database
	 *
	 * @param Route $route
	 *
	 * @return int|false The id of the route if it exists, or false
	 */
	public function getExistingRouteId(Route $route)
	{
		//verify the route can be added to the database
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('route_id')
			->from('route_archive')
			->where('route_name = :name')
			->andWhere('zone_id = :zoneId')
			->andWhere('owner_user_id = ' . $route->ownerUserId)
			->setParameter(':name', $route->routeName, Constants::SILEX_PARAM_STRING)
			->setParameter(':zoneId', $route->zoneId);
		if(empty($rows = $qb->execute()->fetchAll()))
		{
			return false;
		}

		return (int)$rows[0]['route_id'];
	}

	public function getRouteName($zoneId, $imageName)
	{
		return "/$zoneId-" . str_replace(" ", "_", $imageName);
	}

	public function getRouteHostingUrl($zoneId, $fileName)
	{
		$ext = "";
		$info = pathinfo($fileName);
		if(!empty($info) && isset($info['extension']))
		{
			$ext = $info['extension'];
		}

		return uniqid("/$zoneId-") . ".$ext";
	}

	/**
	 * Returns basic info about all routes within the passed in zone
	 *
	 * @param int $zoneId
	 *
	 * @return array
	 */
	public function getRoutesInZone(int $zoneId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			'ra.route_id',
			'ra.route_name',
			'z.zone_name',
			'ra.wheelchair_accessible',
			'ra.blind_accessible',
			'ra.hearing_accessible'
		)
			->from('route_archive', 'ra')
			->leftJoin('ra', 'zone', 'z', 'ra.zone_id = z.zone_id')
			->where('ra.zone_id = :zone_id')
			->setParameter('zone_id', $zoneId, Constants::SILEX_PARAM_INT);
		$routes = $qb->execute()->fetchAll();

		foreach($routes as &$route)
		{
			//convert the enums strings to boolean
			$route['wheelchair_accessible'] = $route['wheelchair_accessible'] === "true";
			$route['blind_accessible'] = $route['blind_accessible'] === "true";
			$route['hearing_accessible'] = $route['hearing_accessible'] === "true";
		}

		return $routes;
	}

	/**
	 * Gets the details of the routes of the passed in zone required to view the routes in a map
	 *
	 * @param int $zoneId
	 *
	 * @return mixed[]
	 */
	public function getRouteMapDetails(int $zoneId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			'ra.route_id',
			'ra.route_name',
			'ra.route_file_url',
			'z.latitude',
			'z.longitude',
			'z.zoom'
		)
			->from('route_archive', 'ra')
			->leftJoin('ra', 'zone', 'z', 'ra.zone_id = z.zone_id')
			->where('z.zone_id = :zone_id')
			->setParameter(':zone_id', $zoneId);

		$details = $qb->execute()->fetchAll();
		foreach($details as &$detail)
		{
			$detail['zoom'] = (int)$detail['zoom'];
		}

		return $details;
	}

	/**
	 * Checks if the passed in route exists within the passed in zone
	 *
	 * @param int $zoneId
	 * @param int $routeId
	 *
	 * @return bool
	 */
	public function routeExists(int $zoneId, int $routeId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			'route_id'
		)
			->from('route_archive')
			->where('route_id = :routeId')
			->andWhere('zone_id = :zoneId')
			->setParameter(':routeId', $routeId, Constants::SILEX_PARAM_INT)
			->setParameter(':zoneId', $zoneId, Constants::SILEX_PARAM_INT);

		return !empty($qb->execute()->fetchAll());
	}
}