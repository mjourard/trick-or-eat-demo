<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 3/27/2017
 * Time: 4:40 PM
 */

namespace TOETests\App\Controller;

use TOE\GlobalCode\Constants;
use TOE\GlobalCode\HTTPCodes;
use TOETests\BaseTestCase;
use TOETests\clsTesterCreds;

class RouteArchiveControllerTest extends BaseTestCase
{

	/**
	 * @group Route-Archive
	 */
	public function testGetRoutes()
	{
		$this->initializeTest(clsTesterCreds::SUPER_ADMIN_EMAIL);

		//test with no routes in the database
		$this->client->request('GET', '/zones/routes/999999');
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);
		$routes = json_decode($this->lastResponse->getContent())->routes;
		self::assertEmpty($routes, "Routes did not return an empty array.");

		//test with one route in the database
		$newRouteId = RouteArchiveControllerTest::addRouteToArchive($this->dbConn, "fill", "fill", 5, "Bus", true, true, true, 1, $this->getLoggedInUserId());
		$this->client->request('GET', '/zones/routes/1');
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);
		$routes = json_decode($this->lastResponse->getContent())->routes;
		self::assertNotEmpty($routes, "Routes did not return a populated array.");
		self::assertEquals($newRouteId, $routes[0]->route_id, "route_id did not match");

		$routeIds = [$newRouteId];

		//test with multiple routes in the database
		$newRouteId = RouteArchiveControllerTest::addRouteToArchive($this->dbConn, "fill2", "fill2", 5, "Bus", true, true, true, 1, $this->getLoggedInUserId());
		$this->client->request('GET', '/zones/routes/1');
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);
		$routes = json_decode($this->lastResponse->getContent())->routes;
		self::assertNotEmpty($routes, "Routes did not return a populated array.");


		$routeIds[] = $newRouteId;


		foreach($routeIds as $index => $routeId)
		{
			self::assertEquals($routeId, $routes[$index]->route_id, "route_id did not match");
			RouteArchiveControllerTest::removeRouteFromArchive($this->dbConn, $routeId);
		}
	}

	/**
	 * @group Route-Archive
	 */
	public function testAddRoute()
	{
		$this->markTestIncomplete();
	}

	/**
	 * @group Route-Archive
	 */
	public function testDeleteRoute()
	{
		$this->markTestIncomplete();
	}

	/**
	 * @param \Doctrine\DBAL\Connection $dbConn
	 * @param                           $url
	 * @param                           $name
	 * @param                           $people
	 * @param                           $type
	 * @param                           $mobile
	 * @param                           $blind
	 * @param                           $hearing
	 * @param                           $zoneId
	 *
	 * @param                           $ownerId
	 *
	 * @return int The id of the route that was just added
	 */
	public static function addRouteToArchive($dbConn, $url, $name, $people, $type, $mobile, $blind, $hearing, $zoneId, $ownerId)
	{
		$mobile = $mobile ? "true" : "false";
		$blind = $blind ? "true" : "false";
		$hearing = $hearing ? "true" : "false";

		$qb = $dbConn->createQueryBuilder();
		$qb->select('route_id')
			->from('route_archive')
			->where("route_file_url = '$url'")
			->andWhere("route_name = '$name'")
			->andWhere("Required_people = $people")
			->andWhere("type = '$type'")
			->andWhere("wheelchair_accessible = '$mobile'")
			->andWhere("blind_accessible = '$blind'")
			->andWhere("hearing_accessible = '$hearing'")
			->andWhere("zone_id = $zoneId");

		if (!empty(($route = $qb->execute()->fetchAll())))
		{
			return $route[0]['route_id'];
		}

		$qb->insert('route_archive')
			->values([
				"route_file_url"        => ":url",
				"route_name"            => ":name",
				"Required_people"       => $people,
				"type"                  => ":type",
				"wheelchair_accessible" => ':mobile',
				"blind_accessible"      => ':blind',
				"hearing_accessible"    => ':hearing',
				"zone_id"               => $zoneId,
				"owner_user_id" => $ownerId
			])
			->setParameter(":url", $url, Constants::SILEX_PARAM_STRING)
			->setParameter(":name", $name, Constants::SILEX_PARAM_STRING)
			->setParameter(":type", $type, Constants::SILEX_PARAM_STRING)
			->setParameter(":mobile", $mobile, Constants::SILEX_PARAM_STRING)
			->setParameter(":blind", $blind, Constants::SILEX_PARAM_STRING)
			->setParameter(":hearing", $hearing, Constants::SILEX_PARAM_STRING);

		$qb->execute();

		$qb = $dbConn->createQueryBuilder();
		$qb->select('route_id')
			->from('route_archive')
			->where("route_file_url = '$url'")
			->andWhere("route_name = '$name'")
			->andWhere("Required_people = $people")
			->andWhere("type = '$type'")
			->andWhere("wheelchair_accessible = '$mobile'")
			->andWhere("blind_accessible = '$blind'")
			->andWhere("hearing_accessible = '$hearing'")
			->andWhere("zone_id = $zoneId");

		if (!empty(($route = $qb->execute()->fetchAll())))
		{
			return $route[0]['route_id'];
		}

		return -1;
	}

	/**
	 * @param \Doctrine\DBAL\Connection $dbConn
	 * @param $routeId
	 */
	public static function removeRouteFromArchive($dbConn, $routeId)
	{
		$qb = $dbConn->createQueryBuilder();
		$qb->delete('route_archive')
			->where("route_id = $routeId");

		$qb->execute();
	}

}
