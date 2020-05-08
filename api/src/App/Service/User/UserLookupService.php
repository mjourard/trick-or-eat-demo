<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 7/10/2017
 * Time: 3:06 AM
 */

namespace TOE\App\Service\User;

use Doctrine\DBAL\Connection;
use TOE\App\Service\BaseDBService;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\HTTPCodes;
use TOE\GlobalCode\ResponseJson;

class UserLookupService extends BaseDBService
{
	public const USER_ROLES_DELIM = ',';

	/**
	 * Gets the user_id of the user from the database with the passed in email
	 *
	 * @param $email
	 *
	 * @return bool|int Returns false if the email is not in the User table. Otherwise, returns the data in the columns matching the email.
	 */
	public function getUserId($email)
	{
		//check database to make sure that user's email isn't already registered
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('user_id')
			->from('user')
			->where('email = :email')
			->setParameter(':email', $email, Constants::SILEX_PARAM_STRING);
		$results = $qb->execute()->fetchAll();
		if(empty($results))
		{
			return false;
		}

		return $results[0]['user_id'];
	}

	/**
	 * @param string $email            The email of the user you need information for
	 * @param array  $columns          The columns in the user table you need information for
	 *
	 * @param bool   $includeUserRoles If set to true, appends a user_roles array of the roles that the user has
	 *
	 * @return bool|array returns false if the email does not exist, or an associative array with the columns and row values requested
	 * @throws UserException
	 */
	public function getUserInfo($email, array $columns, $includeUserRoles = false)
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

		foreach($columns as &$column)
		{
			if(!isset($accepted[$column]))
			{
				throw new UserException("Column $column not a column in the User table");
			}
			$column = "u.$column";
		}
		if($includeUserRoles)
		{
			$columns[] = "GROUP_CONCAT(ur.role SEPARATOR '" . self::USER_ROLES_DELIM . "') as user_roles";
		}

		$qb = $this->dbConn->createQueryBuilder();
		$qb->select($columns)
			->from('user', 'u')
			->where('email = :email')
			->setParameter(':email', $email, Constants::SILEX_PARAM_STRING);

		if($includeUserRoles)
		{
			$qb->leftJoin('u', 'user_role', 'ur', 'u.user_id = ur.user_id');
		}

		$results = $qb->execute()->fetchAll();
		if(empty($results))
		{
			return false;
		}
		if(count($results) > 1)
		{
			throw new UserException("Database inconsistency; more than one user registered with the passed in email.");
		}
		$results = $results[0];
		if($includeUserRoles)
		{
			$results['user_roles'] = explode(self::USER_ROLES_DELIM, $results['user_roles']);
		}
		$columns = ['region_id', 'user_id'];
		foreach($columns as $column)
		{
			if (isset($results[$column]))
			{
				$results[$column] = (int)$results[$column];
			}
		}

		return $results;
	}

	/**
	 * Gets a user entity object. For controller use only.
	 *
	 * @param int   $userId The id of the user to get an entity for
	 * @param array $userInfoArr The user info array for that user to combine with the output
	 *
	 * @return array|null
	 */
	public function getUserEntity(int $userId, array $userInfoArr)
	{
		$qb = $this->dbConn->createQueryBuilder();

		$qb->select(
			'u.first_name',
			'u.last_name',
			'u.region_id',
			'r.region_name',
			'c.country_id',
			'c.country_name',
			'e.event_id',
			'e.event_name',
			't.team_id',
			't.name as team_name'
		)
			->from('user', 'u')
			->leftJoin('u', 'member', 'm', 'u.user_id = m.user_id')
			->leftJoin('m', 'event', 'e', 'm.event_id = e.event_id')
			->leftJoin('m', 'team', 't', 'm.team_id = t.team_id')
			->leftJoin('u', 'region', 'r', 'u.region_id = r.region_id')
			->leftJoin('r', 'country', 'c', 'r.country_id = c.country_id')
			->where("u.user_id = :user_id")
		->setParameter(':user_id', $userId);

		$results = $qb->execute()->fetch();

		//TODO: visit if we want to be removing old links for users to events. If not, possibly store past event info in a different table
		//TODO: also, if a user is signed up for multiple events (i.e. really committed members or event organizers) this will fail...

		$results['region_id'] = (int)$results['region_id'];
		$results['country_id'] = (int)$results['country_id'];
		$results['event_id'] = $results['event_id'] !== null ? (int)$results['event_id'] : null;
		$results['team_id'] = $results['team_id'] !== null ? (int)$results['team_id'] : null;
		$results = array_merge($results, $userInfoArr);

		return $results;
	}

	/**
	 * Registers a user to the TOE website, creating their account
	 *
	 * @param string $email
	 * @param string $password
	 * @param string $firstName
	 * @param string $lastName
	 * @param string $regionId
	 * @param string $role
	 *
	 * @return string The id of the user that was created
	 * @throws UserException
	 * @throws \Doctrine\DBAL\ConnectionException
	 */
	public function registerUser($email, $password, $firstName, $lastName, $regionId, $role = Constants::ROLE_PARTICIPANT)
	{
		try
		{
			$this->dbConn->beginTransaction();
			//insert user data into DB
			$qb = $this->dbConn->createQueryBuilder();
			$qb->insert('user')
				->values([
					'email'      => ':email',
					'password'   => ':password',
					'first_name' => ':first_name',
					'last_name'  => ':last_name',
					'region_id'  => ':region_id'
				])
				->setParameter(':email', $email)
				->setParameter(':password', password_hash($password, PASSWORD_DEFAULT))
				->setParameter(':first_name', $firstName)
				->setParameter(':last_name', $lastName)
				->setParameter(':region_id', $regionId);
			if(!$qb->execute() === 0)
			{
				$this->dbConn->rollBack();
				throw new UserException("Unable to insert a new row into the user table");
			}
			$userId = $this->dbConn->lastInsertId();
			$qb = $this->dbConn->createQueryBuilder();
			$qb->insert('user_role')
				->values([
					'user_id' => $userId,
					'role'    => ':role'
				])
				->setParameter(':role', $role, Constants::SILEX_PARAM_STRING);
			if(!$qb->execute() === 0)
			{
				$this->dbConn->rollBack();
				throw new UserException("Unable to insert a new row into the user_role table");
			}
			$this->dbConn->commit();
		}
		catch(\Exception $ex)
		{
			$this->dbConn->rollBack();
			throw new UserException("An unkown error occurred while registering a new user: " . $ex->getMessage());
		}

		return $userId;
	}

	/**
	 * Updates the accessibility info of a user
	 *
	 * @param string $userId
	 * @param bool   $mobility
	 * @param bool   $visual
	 * @param bool   $hearing
	 *
	 * @return bool true if the update changed anything in the database, false otherwise
	 */
	public function updateAccessibilityInfo($userId, bool $mobility, bool $visual, bool $hearing)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$this->boolToEnum($mobility);
		$this->boolToEnum($visual);
		$this->boolToEnum($hearing);
		$qb->update('user')
			->set('mobility', ':mobility')
			->set('visual', ':visual')
			->set('hearing', ':hearing')
			->where('user_id = :user_id')
			->setParameter(':mobility', $mobility)
			->setParameter(':visual', $visual)
			->setParameter(':hearing', $hearing)
			->setParameter(':user_id', $userId);
		$affectedRows = $qb->execute();

		return $affectedRows > 0;
	}

	/**
	 * Updates the first name, last name and region id of the passed in user
	 *
	 * @param int    $userId
	 * @param string $firstName
	 * @param string $lastName
	 * @param int       $regionId
	 *
	 * @return bool true if the user had any values changed, false otherwise
	 */
	public function updateBaseInfo(int $userId, string $firstName, string $lastName, $regionId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->update('user')
			->set('first_name', ':first_name')
			->set('last_name', ':last_name')
			->set('region_id', ':region_id')
			->where('user_id = :user_id')
			->setParameter(':first_name', $firstName, Constants::SILEX_PARAM_STRING)
			->setParameter(':last_name', $lastName, Constants::SILEX_PARAM_STRING)
			->setParameter(':region_id', $regionId, Constants::SILEX_PARAM_STRING)
			->setParameter(':user_id', $userId);
		return $qb->execute() === 1;
	}
}