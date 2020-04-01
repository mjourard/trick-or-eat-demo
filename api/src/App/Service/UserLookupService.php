<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 7/10/2017
 * Time: 3:06 AM
 */

namespace TOE\App\Service;

use Doctrine\DBAL\Connection;
use TOE\GlobalCode\clsConstants;

class UserLookupService
{
	/** @var Connection */
	private $dbConn;

	public function __construct(Connection $dbConn)
	{
		$this->dbConn = $dbConn;
	}

	/**
	 * Gets the user_id of the user from the database with the passed in email
	 *
	 * @param $email
	 *
	 * @return bool|array Returns false if the email is not in the User table. Otherwise, returns the data in the columns matching the email.
	 */
	public function GetUserId($email)
	{
		//check database to make sure that user's email isn't already registered
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('user_id')
			->from('user')
			->where('email = :email')
			->setParameter(':email', $email, clsConstants::SILEX_PARAM_STRING);

		$results = $qb->execute()->fetchAll();
		if (empty($results))
		{
			return false;
		}

		return $results[0]['user_id'];
	}

	/**
	 * @param string $email   The email of the user you need information for
	 * @param array  $columns The columns in the user table you need information for
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function GetUserInfo($email, array $columns)
	{
		$accepted = [
			"user_id"     => 0,
			"email"       => 0,
			"password"    => 0,
			"first_name"  => 0,
			"last_name"   => 0,
			"date_joined" => 0,
			"region_id"   => 0,
			"hearing"     => 0,
			"visual"      => 0,
			"mobility"    => 0
		];

		foreach ($columns as $column)
		{
			if (!isset($accepted[$column]))
			{
				throw new \Exception("Column $column not a column in the User table");
			}
		}

		$qb = $this->dbConn->createQueryBuilder();
		$qb->select($columns)
			->from('user')
			->where('email = :email')
			->setParameter(':email', $email, clsConstants::SILEX_PARAM_STRING);

		$results = $qb->execute()->fetchAll();
		if (empty($results))
		{
			return false;
		}

		return $results[0];
	}
}