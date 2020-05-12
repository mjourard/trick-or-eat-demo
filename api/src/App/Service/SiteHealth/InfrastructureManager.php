<?php
declare(strict_types=1);

namespace TOE\App\Service\SiteHealth;


use Monolog\Logger;
use TOE\App\Service\BaseDBService;

class InfrastructureManager extends BaseDBService
{
	/**
	 * Checks if the database of the site is ready, and logs using the passed in logger if it isn't
	 *
	 * @param Logger $logger
	 *
	 * @return bool
	 */
	public function isDbReady(Logger $logger)
	{
		try
		{
			$this->dbConn->query("SHOW DATABASES");
		}
		catch(\Exception $ex)
		{
			$logger->warn("Database is down", [
				'ex_type' => get_class($ex),
				'msg' => $ex->getMessage()
			]);
			return false;
		}
		return true;
	}
}