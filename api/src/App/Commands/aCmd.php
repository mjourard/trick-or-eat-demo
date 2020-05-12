<?php
declare(strict_types=1);


namespace TOE\App\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use TOE\App\ServiceContainer;

class aCmd extends Command
{
	protected $container;

	public function __construct(ServiceContainer $container)
	{
		$this->container = $container;
		parent::__construct();
	}

	protected function notifyUserOfDBType(OutputInterface $output)
	{
		switch(($name = $this->container->dbConn->getDriver()->getName()))
		{
			case 'pdo_mysql':
				$msg = "regular MySQL database ($name)";
				break;
			case 'rds-data':
				$msg = "AWS Aurora database ($name)";
				break;
			default:
				$msg = "unknown  ($name)";
		}
		$output->writeln("dbtype: $msg");
	}

	/**
	 * Checks if the currently configured database connection is for the aurora serverless data api
	 *
	 * @return bool
	 */
	protected function isAurora()
	{
		return $this->container->dbConn->getDriver()->getName() === 'rds-data';
	}
}