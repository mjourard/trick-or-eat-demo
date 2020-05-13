<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 3/27/2017
 * Time: 4:40 PM
 */

namespace TOETests\App\Controller;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use TOE\App\Service\Route\Archive\iObjectStorage;
use TOE\App\Service\Route\Archive\RouteManager;
use TOE\App\Service\Route\Assignment\AssignmentManager;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\Env;
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
		$this->setDatabaseConnection();

		$goodRoute = [
			'zone_id' => (string)ZoneControllerTest::TEST_ZONE_ID,
			'type' => AssignmentManager::ROUTE_TYPE_WALK,
			'mobility' => $this->boolToEnum(true),
			'visual' => $this->boolToEnum(true),
			'hearing' => $this->boolToEnum(true)
		];

		$goodFilepath = __DIR__ . '/../../POST-PUT-data/kmz-files/Trick-Or-Eat_Zone_AB.kmz';
		$fileContents = file_get_contents($goodFilepath);
		$tempFile = self::createTempRoutefileCopy($goodFilepath, __FUNCTION__);
		$file = new UploadedFile($tempFile, 'trikc-or-eat-zone-ab.kmz', 'application/vnd.google-earth.kmz');


		//attempt with a user that shouldn't be able to upload routes
		$this->login(clsTesterCreds::NORMAL_USER_EMAIL);
		$this->client->request('POST', '/zones/routes', $goodRoute);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_AUTHORIZED);

		$this->login(clsTesterCreds::ORGANIZER_EMAIL);
		//attempt with a zone that doesn't exist
		$badRoute = $goodRoute;
		$badRoute['zone_id'] = (string)ZoneControllerTest::BAD_ZONE_ID;
		$this->client->request('POST', '/zones/routes', $badRoute, ['file' => $file]);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_BAD_REQUEST);
		$message = json_decode($this->lastResponse->getContent())->message;
		self::assertStringContainsStringIgnoringCase("zone", $message, "Error message did not mention the zone not existing");

		//try it without a file
		$this->client->request('POST', '/zones/routes', $goodRoute, []);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_BAD_REQUEST);
		$message = json_decode($this->lastResponse->getContent())->message;
		self::assertStringContainsString("file", $message, "Error message did not mention missing a file");


		//attempt with a route type that doesn't exist
		$badRoute = $goodRoute;
		$badRoute['type'] = 'Fly';
		$this->client->request('POST', '/zones/routes', $badRoute, ['file' => $file]);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_BAD_REQUEST);
		$message = json_decode($this->lastResponse->getContent())->message;
		self::assertStringContainsStringIgnoringCase("route type", $message, "Error message did not mention the route type not existing");
		if(file_exists($tempFile))
		{
			unlink($tempFile);
		}

		$storageTypes = ['s3', 'file'];
		$priorStorageType = Env::get(Env::TOE_OBJECT_STORAGE_TYPE);
		foreach($storageTypes as $storageType)
		{
			Env::set(Env::TOE_OBJECT_STORAGE_TYPE, $storageType);
			Env::get(Env::TOE_OBJECT_STORAGE_TYPE, true);
			$tempFile = self::createTempRoutefileCopy($goodFilepath, __FUNCTION__);
			$file = new UploadedFile($tempFile, 'trikc-or-eat-zone-ab.kmz', 'application/vnd.google-earth.kmz');
			//attempt with a real route
			$this->client->request('POST', '/zones/routes', $goodRoute, ['file' => $file]);
			$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);
			$content = json_decode($this->lastResponse->getContent());
			self::assertNotEmpty($content);
			self::assertNotEmpty($content->route_id);
			$routeId = $content->route_id;

			//check the newly inserted route id data
			/** @var iObjectStorage $objectStorage */
			$objectStorage = $this->app['route.object_storage'];
			/** @var RouteManager $routeManager */
			$routeManager = $this->app['route.manager'];

			$route = $routeManager->getRouteInfo($routeId);
			self::assertNotEmpty($route);
			$fp = $objectStorage->getRouteFile($route);
			$newContents = stream_get_contents($fp);
			fclose($fp);
			self::assertEquals($fileContents, $newContents, "uploaded file contents does not match");
			self::assertTrue($objectStorage->routeFileExists($route), "Could not find the route file");

			if(file_exists($tempFile))
			{
				unlink($tempFile);
			}
			$route = $objectStorage->deleteRouteFile($route);
			self::assertTrue($routeManager->retireRoute($route->getRouteId()));
			self::assertFalse($objectStorage->routeFileExists($route), "Unable to delete route file from permanent object store");
		}

		Env::set(Env::TOE_OBJECT_STORAGE_TYPE, $priorStorageType);
	}

	/**
	 * @group Route-Archive
	 */
	public function testDeleteRoute()
	{
		self::markTestIncomplete();
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

		if(!empty(($route = $qb->execute()->fetchAll())))
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
				"owner_user_id"         => $ownerId
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

		if(!empty(($route = $qb->execute()->fetchAll())))
		{
			return $route[0]['route_id'];
		}

		return -1;
	}

	/**
	 * @param \Doctrine\DBAL\Connection $dbConn
	 * @param                           $routeId
	 */
	public static function removeRouteFromArchive($dbConn, $routeId)
	{
		$qb = $dbConn->createQueryBuilder();
		$qb->delete('route_archive')
			->where("route_id = $routeId");

		$qb->execute();
	}

	/**
	 * Copies a route file to a temp file
	 *
	 * @param string $routefile
	 * @param string $prefix
	 *
	 * @return false|string
	 */
	public static function createTempRoutefileCopy($routefile, $prefix)
	{
		$fileContents = file_get_contents($routefile);
		self::assertNotEmpty($fileContents);
		$tempFile = tempnam(sys_get_temp_dir(), $prefix);
		file_put_contents($tempFile, $fileContents);
		return $tempFile;
	}

}
