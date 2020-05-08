<?php
declare(strict_types=1);

namespace TOE\App\Service;


use Doctrine\DBAL\Connection;

class BaseDBService
{
	/** @var Connection */
	protected $dbConn;

	public function __construct(Connection $dbConn)
	{
		$this->dbConn = $dbConn;
	}

	/**
	 * Converts the passed in boolean to a string of 'true' or 'false' for working with the mysql enums
	 *
	 * @param bool $bool
	 */
	public function boolToEnum(bool &$bool)
	{
		$bool = $bool ? 'true' : 'false';
	}
}