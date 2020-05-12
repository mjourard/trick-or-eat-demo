<?php
declare(strict_types=1);


namespace TOE\App;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Monolog\Logger;
use TOE\App\Service\AWS\AuroraDataAPIWrapper;

class ServiceContainer
{
	/**
	 * @var Connection
	 */
	public $dbConn;

	/** @var Logger */
	public $logger;

	public $configs;

	public function __construct(Connection $db, Logger $logger, array $configs)
	{
		$this->dbConn = $db;
		$this->logger = $logger;
		$this->configs = $configs;
	}

	public function getDbConfigs()
	{
		return $this->configs['db.options'];
	}

	/**
	 * @param array $dbOptions
	 *
	 * @return Connection
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function setNewConnection($dbOptions)
	{
		$this->dbConn = DriverManager::getConnection($dbOptions);
		return $this->dbConn;
	}
}