<?php

/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 1/30/2017
 * Time: 1:30 PM
 */

namespace TOETests\App\Controller;

use TOE\App\Controller\ZoneController;
use TOE\GlobalCode\clsHTTPCodes;
use TOETests\BaseTestCase;
use TOETests\clsTestConstants;
use TOETests\clsTesterCreds;

class ZoneControllerTest extends BaseTestCase
{
	const NEW_ZONE_NAME = "zoneControllerTest-newZone";
	const TEST_ZONE_ID  = 1;
	const BAD_ZONE_ID   = 999999;


	const BAD_ZONE_STATUS              = "%41414d";
	const DEFAULT_CREATE_OBJECT_PATH     = "/zones/create.json";
	const DEFAULT_EDIT_OBJECT_PATH     = "/zones/edit.json";
	const RESTORATION_EDIT_OBJECT_PATH = "/zones/edit-original.json";

	const LONG_STRING = "asjas.,mzncfokjas;lkfdjwoiejf;lkajdlkfjijfqoije;lkajsd;lkfjaijfd;lkajs;dlkfjasoifdj;lkewj;lkzjfoijas;lkdjfa;ijewoija;lkjfdoijz;lkjfoij;lkjwoij;lkj;ljzx;lkcj;oijoijfpoijqlkefjasjas.,mzncfokjas;lkfdjwoiejf;lkajdlkfjijfqoije;lkajsd;lkfjaijfd;lkajs;dlkfjasoifdj;lkewj;lkzjfoijas;lkdjfa;ijewoija;lkjfdoijz;lkjfoij;lkjwoij;lkj;ljzx;lkcj;oijoijfpoijqlkefjAAasjas.,mzncfokjas;lkfdjwoiejf;lkajdlkfjijfqoije;lkajsd;lkfjaijfd;lkajs;dlkfjasoifdj;lkewj;lkzjfoijas;lkdjfa;ijewoija;lkjfdoijz;lkjfoij;lkjwoij;lkj;ljzx;lkcj;oijoijfpoijqlkefjasjas.,mzncfokjas;lkfdjwoiejf;lkajdlkfjijfqoije;lkajsd;lkfjaijfd;lkajs;dlkfjasoifdj;lkewj;lkzjfoijas;lkdjfa;ijewoija;lkjfdoijz;lkjfoij;lkjwoij;lkj;ljzx;lkcj;oijoijfpoijqlkefjAAasjas.,mzncfokjas;lkfdjwoiejf;lkajdlkfjijfqoije;lkajsd;lkfjaijfd;lkajs;dlkfjasoifdj;lkewj;lkzjfoijas;lkdjfa;ijewoija;lkjfdoijz;lkjfoij;lkjwoij;lkj;ljzx;lkcj;oijoijfpoijqlkefjasjas.,mzncfokjas;lkfdjwoiejf;lkajdlkfjijfqoije;lkajsd;lkfjaijfd;lkajs;dlkfjasoifdj;lkewj;lkzjfoijas;lkdjfa;ijewoija;lkjfdoijz;lkjfoij;lkjwoij;lkj;ljzx;lkcj;oijoijfpoijqlkefjAA";
	const BAD_VALUES  = ["zone_name"               => [self::LONG_STRING],
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
		$this->SetDatabaseConnection();
		$this->SetClient();

		$this->LoginAsAdmin();
		//TODO: test that only users of type organizer can create a zone

		$this->LoadJSONObject(clsTestConstants::TEST_DATA_FOLDER_PATH . self::DEFAULT_CREATE_OBJECT_PATH);
		//test sending in bad values

		foreach (self::BAD_VALUES as $key => $valArray)
		{
			foreach ($valArray as $value)
			{
				$this->client->request('POST', '/zones/create', $this->GetModifiedJSONObject([$key => $value]));
				$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);
				$message = json_decode($this->lastResponse->getContent())->message;
				$this->assertTrue(stripos($message, $key) !== false, "Error message did not contain the key in error: $key. Message: '$message'");
			}
		}

		//create a good one
		$this->client->request('POST', '/zones/create', $this->GetModifiedJSONObject());
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
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
		$this->SetDatabaseConnection();
		$this->SetClient();
		$this->LoginAsAdmin();

		//test editing a zone when you're not an organizer or higher

		//test sending in an ID of a zone that does not exist
		$this->LoadJSONObject(clsTestConstants::TEST_DATA_FOLDER_PATH . self::DEFAULT_EDIT_OBJECT_PATH);
		$this->client->request('PUT', '/zones/edit', $this->GetModifiedJSONObject(["zone_id" => self::BAD_ZONE_ID]));
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_NOT_FOUND);

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
				$this->client->request('PUT', '/zones/edit', $this->GetModifiedJSONObject([$key => $value]));
				$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);
				$this->assertTrue(stripos(json_decode($this->lastResponse->getContent())->message, $key) !== false, "Error message did not contain the key in error: $key");
			}
		}

		/**
		 * 'zone_name'               => clsConstants::SILEX_PARAM_STRING,
		 * 'central_parking_address' => clsConstants::SILEX_PARAM_STRING,
		 * 'central_building_name'   => clsConstants::SILEX_PARAM_STRING,
		 * 'zone_radius_meter'       => clsConstants::SILEX_PARAM_INT,
		 * 'houses_covered'          => clsConstants::SILEX_PARAM_INT,
		 * 'zoom' =>  clsConstants::SILEX_PARAM_INT,
		 * 'latitude' =>  clsConstants::SILEX_PARAM_INT,
		 * 'longitude' =>  clsConstants::SILEX_PARAM_INT */

		//test sending in good values
		$goodObj = $this->GetModifiedJSONObject();
		$this->client->request('PUT', '/zones/edit', $goodObj);
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
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
			$this->assertEquals($goodObj[$key], $value, "column $key did not match after update.");
		}

		//restore the database
		$this->LoadJSONObject(clsTestConstants::TEST_DATA_FOLDER_PATH . self::RESTORATION_EDIT_OBJECT_PATH);
		$this->client->request('PUT', '/zones/edit', $this->GetModifiedJSONObject());
	}

	/**
	 * @group Zone
	 */
	public function testGetZones()
	{
		$this->Login(clsTesterCreds::NORMAL_USER_EMAIL);
		//attempt to call testGetZones
		$this->client->request("GET", "/zones/9/all");
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_NOT_AUTHORIZED);
		$this->markTestIncomplete();
	}

	/**
	 * @group Zone
	 */
	public function testGetZone()
	{
		$this->markTestIncomplete();
	}

	/**
	 * @group Zone
	 */
	public function testSetZoneStatus()
	{
		$this->SetClient();
		$this->SetDatabaseConnection();
		$statuses = ['inactive', 'retired', 'active'];

		$this->Login(clsTesterCreds::NORMAL_USER_EMAIL);
		$this->client->request('PUT', '/zones/status', $this->getStatusPostObj(self::TEST_ZONE_ID, $statuses[0]));
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_NOT_AUTHORIZED);

		$this->Login(clsTesterCreds::ORGANIZER_EMAIL);

		//test with a zone that does not exist
		$this->client->request('PUT', '/zones/status', $this->getStatusPostObj(self::BAD_ZONE_ID, $statuses[0]));
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_NOT_FOUND);

		//test with a bad status
		$this->client->request('PUT', '/zones/status', $this->getStatusPostObj(self::TEST_ZONE_ID, self::BAD_ZONE_STATUS));
		$this->BasicResponseCheck(clsHTTPCodes::CLI_ERR_BAD_REQUEST);

		//attempt to change to each status

		foreach ($statuses as $status)
		{
			$this->client->request('PUT', '/zones/status', $this->getStatusPostObj(self::TEST_ZONE_ID, $status));
			$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
			$qb = $this->dbConn->createQueryBuilder();

			$qb->select('status')
				->from('zone')
				->where('zone_id = ' . self::TEST_ZONE_ID);

			$row = $qb->execute()->fetchAll();
			$this->assertNotEmpty($row);
			$this->assertEquals($status, $row[0]['status']);
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