<?php
declare(strict_types=1);

namespace TOE\App\Service\Event;


use TOE\App\Service\BaseDBService;
use TOE\GlobalCode\Constants;

class RegistrationManager extends BaseDBService
{
	/**
	 * Gets the first event_id that the user is registered to where they aren't already on a team.
	 *
	 * @param int $userId
	 *
	 * @return array
	 */
	public function getEventRegisteredAtByUser(int $userId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('event_id')
			->from('member')
			->where("user_id = :user_id")
			->andWhere('team_id is NULL')
			->setParameter(':user_id', $userId)
			->setMaxResults(1);
		$results = $qb->execute()->fetch();
		if(empty($results))
		{
			return [];
		}
		$results['event_id'] = (int)$results['event_id'];

		return $results;
	}

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
		$q = "
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
}