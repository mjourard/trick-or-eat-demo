<?php
declare(strict_types=1);

namespace TOE\App\Service\Bus;


use TOE\App\Service\BaseDBService;

class BusManager extends BaseDBService
{
	//TODO: implement bus handling logic

	/**
	 * @param string    $busName               The name of the bus
	 * @param \DateTime $departFromEvent       The time that the bus leaves from the central event, saves as the start_time
	 * @param \DateTime $departAfterCollection The time the bus leaves after participants have collected their food to drop them off back at the main location
	 * @param int       $zoneId                The id the bus will be doing drops to
	 *
	 * @return int|false false if the new bus could not be inserted, otherwise return the new bus id
	 */
	public function addBus(string $busName, \DateTime $departFromEvent, \DateTime $departAfterCollection, int $zoneId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->insert("bus")
			->values([
				'bus_name' => ':bus_name',
				'start_time' => ':start_time',
				'end_time' => ':end_time',
				'zone_id' => ':zone_id'
			])
			->setParameter(':bus_name', $busName)
			->setParameter(':start_time', $departFromEvent->format('Y-m-d H:i:s'))
			->setParameter(':end_time', $departAfterCollection->format('Y-m-d H:i:s'))
			->setParameter(':zone_id', $zoneId);

		if ($qb->execute() === 0)
		{
			return false;
		}
		if (empty($busId = $this->dbConn->lastInsertId()))
		{
			return false;
		}
		return (int)$busId;
	}
}