<?php

/**
 * Created by PhpStorm.
 * User: LENOVO-T430
 * Date: 11/11/2016
 * Time: 12:29 PM
 */

namespace TOETests\App\Controller;

use TOE\GlobalCode\HTTPCodes;
use TOETests\BaseTestCase;

class RegionControllerTest extends BaseTestCase
{
	const BAD_COUNTRY_ID  = -1;
	const VALID_NON_EXISTANT_ID = 99999;

	/**
	 * @group Region
	 */
	public function testGetCountries()
	{

		$this->setClient();
		$this->client->request('GET', '/countries');
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);

		$content = json_decode($this->lastResponse->getContent());
		$this->assertTrue($content->success);
		$countries = $content->countries;
		$canadaFound = false;

		foreach ($countries as $country)
		{
			if (strtolower($country->country_name) === 'canada')
			{
				$canadaFound = true;
			}
		}

		$this->assertTrue($canadaFound, "Canada was not found in the countries returned");
	}

	/**
	 * @group Region
	 */
	public function testGetRegions()
	{
		$this->setClient();
		//test sending valid country_id's

		$countryCodes = [
			'canada' => 1
		];

		$expectedRegions = [
			'canada' => [
				1  => "Alberta",
				2  => "British Columbia",
				3  => "Manitoba",
				4  => "New Brunswick",
				5  => "Newfoundland and Labrador",
				6  => "Northwest Territories",
				7  => "Nova Scotia",
				8  => "Nunavut",
				9  => "Ontario",
				10 => "Prince Edward Island",
				11 => "Quebec",
				12 => "Saskatchewan",
				13 => "Yukon",
			]
		];

		foreach ($countryCodes as $country => $id)
		{
			$this->client->request('GET', '/regions/' . $id);
			$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);
			$content = json_decode($this->lastResponse->getContent());
			$this->assertCount(count($expectedRegions[$country]), $content->regions);
			foreach ($content->regions as $row)
			{
				$this->assertEquals($expectedRegions[$country][$row->region_id], $row->region_name, "Region name did not match the database value");
			}

		}

		//attempt to send a bad country id
		$this->client->request('GET', '/regions/' . self::BAD_COUNTRY_ID);
		$this->basicResponseCheck(HTTPCodes::CLI_ERR_NOT_FOUND);

		//attempt to send an ID of a country that does not exist
		$this->client->request('GET', '/regions/' . self::VALID_NON_EXISTANT_ID);
		$this->basicResponseCheck(HTTPCodes::SUCCESS_NO_CONTENT);
	}
}
