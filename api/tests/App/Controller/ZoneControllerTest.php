<?php
declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 1/30/2017
 * Time: 1:30 PM
 */

namespace TOETests\App\Controller;

use TOE\App\Controller\ZoneController;
use TOE\GlobalCode\HTTPCodes;
use TOETests\BaseTestCase;
use TOETests\clsTestConstants;
use TOETests\clsTesterCreds;

class ZoneControllerTest extends BaseTestCase
{
	public const NEW_ZONE_NAME = "zoneControllerTest-newZone";
	public const TEST_ZONE_ID  = 1;
	public const BAD_ZONE_ID   = 999999;


	public const BAD_ZONE_STATUS              = "%41414d";
	public const DEFAULT_CREATE_OBJECT_PATH     = "/zones/create.json";
	public const DEFAULT_EDIT_OBJECT_PATH     = "/zones/edit.json";
	public const RESTORATION_EDIT_OBJECT_PATH = "/zones/edit-original.json";

	public const LONG_STRING = "asjas.,mzncfokjas;lkfdjwoiejf;lkajdlkfjijfqoije;lkajsd;lkfjaijfd;lkajs;dlkfjasoifdj;lkewj;lkzjfoijas;lkdjfa;ijewoija;lkjfdoijz;lkjfoij;lkjwoij;lkj;ljzx;lkcj;oijoijfpoijqlkefjasjas.,mzncfokjas;lkfdjwoiejf;lkajdlkfjijfqoije;lkajsd;lkfjaijfd;lkajs;dlkfjasoifdj;lkewj;lkzjfoijas;lkdjfa;ijewoija;lkjfdoijz;lkjfoij;lkjwoij;lkj;ljzx;lkcj;oijoijfpoijqlkefjAAasjas.,mzncfokjas;lkfdjwoiejf;lkajdlkfjijfqoije;lkajsd;lkfjaijfd;lkajs;dlkfjasoifdj;lkewj;lkzjfoijas;lkdjfa;ijewoija;lkjfdoijz;lkjfoij;lkjwoij;lkj;ljzx;lkcj;oijoijfpoijqlkefjasjas.,mzncfokjas;lkfdjwoiejf;lkajdlkfjijfqoije;lkajsd;lkfjaijfd;lkajs;dlkfjasoifdj;lkewj;lkzjfoijas;lkdjfa;ijewoija;lkjfdoijz;lkjfoij;lkjwoij;lkj;ljzx;lkcj;oijoijfpoijqlkefjAAasjas.,mzncfokjas;lkfdjwoiejf;lkajdlkfjijfqoije;lkajsd;lkfjaijfd;lkajs;dlkfjasoifdj;lkewj;lkzjfoijas;lkdjfa;ijewoija;lkjfdoijz;lkjfoij;lkjwoij;lkj;ljzx;lkcj;oijoijfpoijqlkefjasjas.,mzncfokjas;lkfdjwoiejf;lkajdlkfjijfqoije;lkajsd;lkfjaijfd;lkajs;dlkfjasoifdj;lkewj;lkzjfoijas;lkdjfa;ijewoija;lkjfdoijz;lkjfoij;lkjwoij;lkj;ljzx;lkcj;oijoijfpoijqlkefjAA";
	public const BAD_VALUES  = ["zone_name"               => [self::LONG_STRING],
						 "central_parking_address" => [self::LONG_STRING],
						 "houses_covered"          => [-1],
						 "zoom"                    => [-1, 0, ZoneController::MAX_ZOOM + 1],
						 "latitude"                => [-91, 91],
						 "longitude"               => [-91, 91]];

	/**
	 * @group Zone
	 */
	public function testCreateZone()
	{
		$this->setDatabaseConnection();
		$this->setClient();

		$this->loginAsAdmin();
		//TODO: test that only users of type organizer can create a zone

		$this->loadJSONObject(clsTestConstants::TEST_DATA_FOLDER_PATH . self::DEFAULT_CREATE_OBJECT_PATH);
		//test sending in bad values

		foreach (self::BAD_VALUES as $key => $valArray)
		{
			foreach ($valArray as $value)
			{
				$this->client->request('POST', '/zones/create', $this->getModifiedJSONObject([$key => $value]));
				$this->basicResponseCheck(HTTPCodes::CLI_ERR_BAD_REQUEST);
				$message = json_decode($this->lastResponse->getContent())->message;
				self::assertTrue(stripos($message, $key) !== false, "Error message did not contain the key in error: $key. Message: '$message'");
			}
		}

		//create a good one
		$this->client->request('POST', '/zones/create', $this->getModifiedJSONObject());
		$this->basicResponseCheck(HTTPCodes::SUCCESS_RESOURCE_CREATED);
		$id = json_decode($this->lastResponse->getContent())->zone->zone_id;

		//delete the one that was created
		$qb = $this->dbConn->createQueryBuilder();
		$qb->delete('zone')
			->where("zone_id = $id");

		$qb->execute();
	}

	/**
	 * @group Zone
	 */
	public function testEditZone()
	{
		//test editing a zone that does not exist
		$this->setDatabaseConnection();
		$this->setClient();
		$this->loginAsAdmin();

		//test editing a zone when you're not an organizer or higher

		//test sending in an ID of a zone that does not exist
		$this->loadJSONObject(clsTestConstants::TEST_DATA_FOLDER_PATH . self::DEFAULT_EDIT_OBJECT_PATH);
		$this->client->request('PUT', '/zones/edit', $this->getModifiedJSONObject(["zone_id" => self::BAD_ZONE_ID]));
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_FOUND);

		//test sending in bad values
		$badVals = [
			"zone_name"               => [self::LONG_STRING],
			"central_parking_address" => [self::LONG_STRING],
			"houses_covered"          => [-1],
			"zoom"                    => [-1, 0, ZoneController::MAX_ZOOM + 1],
			"latitude"                => [-91, 91],
			"longitude"               => [-91, 91]
		];

		foreach ($badVals as $key => $valArray)
		{
			foreach ($valArray as $value)
			{
				$this->client->request('PUT', '/zones/edit', $this->getModifiedJSONObject([$key => $value]));
				$this->basicResponseCheck(HTTPCodes::CLI_ERR_BAD_REQUEST);
				self::assertTrue(stripos(json_decode($this->lastResponse->getContent())->message, $key) !== false, "Error message did not contain the key in error: $key");
			}
		}

		/**
		 * 'zone_name'               => constants::SILEX_PARAM_STRING,
		 * 'central_parking_address' => constants::SILEX_PARAM_STRING,
		 * 'central_building_name'   => constants::SILEX_PARAM_STRING,
		 * 'zone_radius_meter'       => constants::SILEX_PARAM_INT,
		 * 'houses_covered'          => constants::SILEX_PARAM_INT,
		 * 'zoom' =>  constants::SILEX_PARAM_INT,
		 * 'latitude' =>  constants::SILEX_PARAM_INT,
		 * 'longitude' =>  constants::SILEX_PARAM_INT */

		//test sending in good values
		$goodObj = $this->getModifiedJSONObject();
		$this->client->request('PUT', '/zones/edit', $goodObj);
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			'zone_name',
			'central_parking_address',
			'central_building_name',
			'zone_radius_meter',
			'houses_covered',
			'zoom',
			'latitude',
			'longitude'
		)
			->from('zone')
			->where("zone_id = {$goodObj['zone_id']}");

		$result = $qb->execute()->fetchAll()[0];
		foreach ($result as $key => $value)
		{
			self::assertEquals($goodObj[$key], $value, "column $key did not match after update.");
		}

		//restore the database
		$this->loadJSONObject(clsTestConstants::TEST_DATA_FOLDER_PATH . self::RESTORATION_EDIT_OBJECT_PATH);
		$this->client->request('PUT', '/zones/edit', $this->getModifiedJSONObject());
	}

	/**
	 * @group Zone
	 */
	public function testGetZones()
	{
		$this->login(clsTesterCreds::NORMAL_USER_EMAIL);
		//attempt to call testGetZones
		$this->client->request("GET", "/zones/9/all");
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_AUTHORIZED);
		self::markTestIncomplete();
	}

	/**
	 * @group Zone
	 */
	public function testGetZone()
	{
		self::markTestIncomplete();
	}

	/**
	 * @group Zone
	 */
	public function testSetZoneStatus()
	{
		$this->setClient();
		$this->setDatabaseConnection();
		$statuses = ['inactive', 'retired', 'active'];

		$this->login(clsTesterCreds::NORMAL_USER_EMAIL);
		$this->client->request('PUT', '/zones/status', $this->getStatusPostObj(self::TEST_ZONE_ID, $statuses[0]));
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_AUTHORIZED);

		$this->login(clsTesterCreds::ORGANIZER_EMAIL);

		//test with a zone that does not exist
		$this->client->request('PUT', '/zones/status', $this->getStatusPostObj(self::BAD_ZONE_ID, $statuses[0]));
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_FOUND);

		//test with a bad status
		$this->client->request('PUT', '/zones/status', $this->getStatusPostObj(self::TEST_ZONE_ID, self::BAD_ZONE_STATUS));
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_BAD_REQUEST);

		//attempt to change to each status

		foreach ($statuses as $status)
		{
			$this->client->request('PUT', '/zones/status', $this->getStatusPostObj(self::TEST_ZONE_ID, $status));
			$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);
			$qb = $this->dbConn->createQueryBuilder();

			$qb->select('status')
				->from('zone')
				->where('zone_id = ' . self::TEST_ZONE_ID);

			$row = $qb->execute()->fetchAll();
			self::assertNotEmpty($row);
			self::assertEquals($status, $row[0]['status']);
		}

	}

	private function getStatusPostObj($zoneId, $status)
	{
		return [
			'zone_id' => $zoneId,
			'status'  => $status
		];
	}
}