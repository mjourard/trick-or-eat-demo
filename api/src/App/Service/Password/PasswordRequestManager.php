<?php
declare(strict_types=1);

namespace TOE\App\Service\Password;


use DateInterval;
use DateTime;
use TOE\App\Service\BaseDBService;
use TOE\GlobalCode\Constants;

class PasswordRequestManager extends BaseDBService
{
	public const MAX_ACTIVE_REQUESTS = 5;
	//Time until password reset token expires (in seconds)
	public const VALID_TIME = 18000;

	public const REQUEST_STATUS_USED = 'used';

	/**
	 * Returns the number of active reset requests for the passed in user
	 *
	 * @param int      $userID
	 * @param DateTime $requestTime The time being compared to the expire time
	 *
	 * @return int The number of active password reset requests
	 */
	public function countRequests($userID, $requestTime)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('COUNT(*) as count')
			->from('password_request')
			->where('user_id = :user_id')
			->andWhere('expired_at > FROM_UNIXTIME(:requestTime)')
			->andWhere("status != 'used'")
			->setParameter(':user_id', $userID)
			->setParameter(':requestTime', $requestTime->getTimestamp());
		return $qb->execute()->fetch()['count'];
	}

	/**
	 * Checks if the passed in user has any more password requests left given the passed in time
	 *
	 * @param $userId
	 * @param $requestTime
	 *
	 * @return bool true if there are no more password reset requests available, false otherwise
	 */
	public function maxRequestsExceeded($userId, $requestTime)
	{
		return $this->countRequests($userId, $requestTime) >= self::MAX_ACTIVE_REQUESTS;
	}

	/**
	 * Updates the status of all password reset requests for the passed in user
	 *
	 * @param $userId
	 * @param $status
	 *
	 * @return int The number of tokens belonging to the user which had their status changed
	 * @throws PasswordResetException
	 */
	public function updateUserResetRequests($userId, $status)
	{
		switch($status)
		{
			case self::REQUEST_STATUS_USED:
				break;
			default:
				throw new PasswordResetException("Bad password_request status passed in: $status");
		}
		$qb = $this->dbConn->createQueryBuilder();
		$qb->update('password_request')
			->set('status', ':status')
			->where('user_id = :user_id')
			->setParameter(':status', $status, Constants::SILEX_PARAM_STRING)
			->setParameter(':user_id', $userId);

		return $qb->execute();
	}

	/**
	 * Inserts a new password reset request into the database
	 *
	 * @param          $userId
	 * @param DateTime $issuedAt
	 * @param DateTime $expiredAt
	 * @param          $uniqueId
	 *
	 * @return bool true if the insert was successful, false otherwise
	 */
	public function insertResetRequest($userId, DateTime $issuedAt, DateTime $expiredAt, $uniqueId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->insert('password_request')
			->values([
				'user_id'    => ':user_id',
				'issued_at'  => 'FROM_UNIXTIME(:issued_at)',
				'expired_at' => 'FROM_UNIXTIME(:expired_at)',
				'unique_id'  => ':unique_id'
			])
			->setParameter(':user_id', $userId)
			->setParameter(':issued_at', $issuedAt->getTimestamp())
			->setParameter(':expired_at', $expiredAt->getTimestamp())
			->setParameter(':unique_id', $uniqueId, Constants::SILEX_PARAM_STRING);
		return $qb->execute() > 0;
	}

	/**
	 * @param DateTime $issueTime
	 *
	 * @return DateTime
	 * @throws \Exception
	 */
	public function getExpireTime(DateTime $issueTime)
	{
		$expiredAt = clone $issueTime;
		$expiredAt->add(new DateInterval('PT' . self::VALID_TIME . 'S'));
		return $expiredAt;
	}

	/**
	 * Checks if the passed in token can be used to reset a password.
	 *
	 * @param WebToken $token       The token being checked
	 * @param DateTime $currentTime The time that the request was sent in
	 *
	 * @throws PasswordResetException Throws an exception if the reset request token is invalid. Exception message will contain the reason.
	 */
	public function checkValidToken(WebToken $token, $currentTime)
	{
		//verify the request exists
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select([
			'expired_at',
			'status'
		])
			->from('password_request')
			->where('user_id = :user_id')
			->andWhere('unique_id = :unique_id')
			->andWhere('issued_at = FROM_UNIXTIME(:issued_at)')
			->andWhere('expired_at = FROM_UNIXTIME(:expired_at)')
			->setParameter(':user_id', $token->getUserId())
			->setParameter(':issued_at', $token->getIssuedAt()->getTimestamp())
			->setParameter(':expired_at', $token->getExpiredAt()->getTimestamp())
			->setParameter(':unique_id', $token->getUniqueId(), Constants::SILEX_PARAM_STRING);
		$result = $qb->execute()->fetchAll();
		if(empty($result))
		{
			throw new PasswordResetException("Password reset request was not found in the database");
		}
		$expiredAt = DateTime::createFromFormat(Constants::DT_FORMAT, $result[0]['expired_at']);
		if($expiredAt < $currentTime)
		{
			throw new PasswordResetException("An expired reset token has been used. Request another reset");
		}
		if($result[0]['status'] === 'used')
		{
			throw new PasswordResetException("This reset token has already been used. Request another reset");
		}
	}

	/**
	 * @param WebToken $token
	 * @param string   $password
	 *
	 * @throws PasswordResetException
	 */
	public function resetPasswordByToken(WebToken $token, $password)
	{
		//Update user's account with new password
		try
		{
			$this->dbConn->beginTransaction();

			//update the password
			$qb = $this->dbConn->createQueryBuilder();
			$qb->update('user')
				->set('password', ':password')
				->where('user_id = :user_id')
				->setParameter(':user_id', $token->getUserId())
				->setParameter(':password', password_hash($password, PASSWORD_DEFAULT));
			$qb->execute();

			//update the password request token
			$qb = $this->dbConn->createQueryBuilder();
			$qb->update('password_request')
				->set('status', ':status')
				->where('user_id = :user_id')
				->andWhere('unique_id = :unique_id')
				->andWhere('issued_at = FROM_UNIXTIME(:issued_at)')
				->andWhere('expired_at = FROM_UNIXTIME(:expired_at)')
				->setParameter(':status', 'used', Constants::SILEX_PARAM_STRING)
				->setParameter(':user_id', $token->getUserId())
				->setParameter(':unique_id', $token->getUniqueId(), Constants::SILEX_PARAM_STRING)
				->setParameter(':issued_at', $token->getIssuedAt()->getTimestamp())
				->setParameter(':expired_at', $token->getExpiredAt()->getTimestamp());
			$qb->execute();

			//commit everything to finish up
			$this->dbConn->commit();
		}
		catch (\Exception $ex)
		{
			$this->dbConn->rollBack();
			throw new PasswordResetException($ex->getMessage(), 0, $ex);
		}
	}
}