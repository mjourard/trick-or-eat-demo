<?php
declare(strict_types=1);

namespace TOE\App\Service\Event;


use PDO;
use TOE\App\Service\BaseDBService;
use TOE\GlobalCode\Constants;

class EventManager extends BaseDBService
{
	/**
	 * check if user is registered for a different event
	 *
	 * @param int $userId
	 *
	 * @param int $eventId
	 *
	 * @return bool
	 */
	public function isRegistered($userId, $eventId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('event_id')
			->from('member')
			->where("user_id = :user_id")
			->andWhere('event_id = :event_id')
			->setParameter(':user_id', $userId)
			->setParameter(':event_id', $eventId);

		return !empty($qb->execute()->fetchAll());
	}

	/**
	 * Gets event data of the passed in event id
	 *
	 * @param $eventId
	 *
	 * @return array An associative array of the event information
	 */
	public function getEvent($eventId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('event_id', 'event_name')
			->from('event')
			->where('event_id = :event_id')
			->setParameter(':event_id', $eventId);

		$event = $qb->execute()->fetch(PDO::FETCH_ASSOC);
		if(empty($event))
		{
			return $event;
		}

		$event['event_id'] = (int)$event['event_id'];

		return $event;
	}

	/**
	 * @param      $userId
	 * @param      $eventId
	 * @param bool $userCanDrive
	 *
	 * @return bool
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function registerForEvent($userId, $eventId, bool $userCanDrive)
	{
		//TODO: modify this so that users can register for multiple events. At the moment, previous event participation is erased.
		$this->boolToEnum($userCanDrive);
		$q =  "
            INSERT INTO member (
              user_id, 
              can_drive, 
              event_id
            ) VALUES (
              :user_id, 
              :can_drive, 
              :event_id
            ) 
            ON DUPLICATE KEY UPDATE 
              can_drive = values(can_drive), 
              event_id = values(event_id)";
		$query = $this->dbConn->prepare($q);
		$query->bindValue('user_id', $userId);
		$query->bindValue('can_drive', $userCanDrive);
		$query->bindValue('event_id', $eventId);

		return $query->execute();
	}

	/**
	 * Gets the events of the passed in region
	 *
	 * @param $regionId
	 *
	 * @return mixed[]
	 */
	public function getEventsByRegion($regionId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			'event_id',
			'event_name'
		)
			->from('event')
			->where('region_id = :region_id')
			->setParameter(':region_id', $regionId);
		$results = $qb->execute()->fetchAll();
		foreach($results as &$event)
		{
			$event['event_id'] = (int)$event['event_id'];
		}

		return $results;
	}

	/**
	 * deregisters a user from the passed in event
	 *
	 * @param $userId
	 * @param $eventId
	 *
	 * @return bool true if the user was successfully deregistered from the event, or false otherwise.
	 */
	public function deregisterUser($userId, $eventId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->delete('member')
			->where('user_id = :user_id')
			->andWhere('event_id = :event_id')
			->setParameter(':user_id', $userId)
			->setParameter(':event_id', $eventId);

		return $qb->execute() > 0;
	}

	/**
	 * @param int $eventId
	 *
	 * @return bool true if the event exists, false otherwise
	 */
	public function eventExists(int $eventId)
	{
		$qb = $this->dbConn->createQueryBuilder();

		$qb->select('event_id')
			->from('event')
			->where('event_id = :event_id')
			->setParameter('event_id', $eventId, Constants::SILEX_PARAM_INT);
		return !empty($qb->execute()->fetchAll());
	}
}