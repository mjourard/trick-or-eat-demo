<?php
declare(strict_types=1);

namespace TOE\App\Commands;


use Aws\RDSDataService\Exception\RDSDataServiceException;
use Aws\RDSDataService\RDSDataServiceClient;
use Aws\S3\Exception\S3Exception;
use Silly\Input\InputArgument;
use Silly\Input\InputOption;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TOE\App\Service\AWS\AuroraDataAPIWrapper;
use TOE\App\Service\AWSConfig;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\Env;

class InitDB extends aCmd
{
	protected static $defaultName = 'init-db';

	protected function configure()
	{
		$this->setDescription('Initializes the mysql database to be ready for use for trick-or-eat');
		$this->addArgument('querydir', InputArgument::OPTIONAL, 'A path to a directory that holds .sql files used to initialize the database. __DIR__ will be replaced by ' . __DIR__, __DIR__ . '/../../../../.docker/mysql/data');
		$this->addOption('wipe', null, InputOption::VALUE_NONE, 'Use this to wipe the contents of the existing database if there is one');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$wipe = $input->getOption('wipe');
		try
		{
			return $this->init($input, $output, $wipe);
		}
		catch(\Exception $ex)
		{
			if(stripos($ex->getMessage(), "The last packet sent successfully to the server was 0 milliseconds ago") !== false)
			{
				$output->writeln("Aurora Serverless might be warming up. Check the current capacity size of the cluster");
				return 1;
			}
			else
			{
				throw $ex;
			}
		}

	}

	protected function init(InputInterface $input, OutputInterface $output, bool $wipe)
	{
		//check to see if the TOE database exists, and if it does, print and exit
		$this->notifyUserOfDBType($output);
		if (!$this->isAurora())
		{
			$this->resetDbConn();
		}
		else
		{
			try
			{
				$this->createAuroraSchema(Constants::DATABASE_NAME);
			}
			catch(RDSDataServiceException $ex)
			{
				if (stripos($ex->getAwsErrorMessage(), "Can't create database '" . Constants::DATABASE_NAME . "'; database exists") !== false)
				{
					$output->writeln("Database already exists, skipping initialization");
				}
				else
				{
					throw $ex;
				}

			}

		}
		$schemas = $this->container->dbConn->query("SHOW DATABASES")->fetchAll();
		if ($wipe)
		{
			try
			{
				$this->container->dbConn->query("DROP DATABASE " . Constants::DATABASE_NAME . ";");
				$this->createAuroraSchema(Constants::DATABASE_NAME);
			}
			catch(\Exception $ex)
			{
				if (stripos($ex->getMessage(), "database doesn't exist") !== false)
				{
					$output->writeln("Database doesn't exist. Nothing to wipe");
				}
				else
				{
					throw $ex;
				}
			}
		}
		else
		{
			if ($this->checkAuroraDBAlreadyInit($output) !== false)
			{
				return 1;
			}
		}

		if(!empty($schemas))
		{
			$hasTOE = false;
			foreach($schemas as $schemaRow)
			{
				if($schemaRow['Database'] === Constants::DATABASE_NAME)
				{
					$hasTOE = true;
				}
			}
			if($hasTOE)
			{
				$tables = $this->container->dbConn->query("SELECT DISTINCT table_name FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '" . Constants::DATABASE_NAME . "'")->fetchAll();
				if (!empty($tables) && !$wipe)
				{
					$output->writeln("'toe' database already exists!");
					$output->writeln("Ensure the data is backed up and delete the schema with 'DROP DATABASE " . Constants::DATABASE_NAME . "'.");
					$output->writeln("Exiting...");

					return 1;
				}
			}
		}

		/** @var FormatterHelper $formatter */
		$formatter = $this->getHelper('formatter');

		$this->container->dbConn->exec("SET FOREIGN_KEY_CHECKS=0");
		$dir = $input->getArgument('querydir');
		$dir = str_replace('__DIR__', __DIR__, $dir);
		$dir = rtrim($dir, "/");
		//run the mysql dump commands
		$files = array_diff(scandir($dir), ['.', '..']);
		foreach($files as $file)
		{
			$query = file_get_contents($dir . "/" . $file);
			//remove all the comments generated from mysqldump
			$query = preg_replace('|/\*.*\*/;|', '', $query);
			$queries = explode(";", $query);
			$formattedLine = $formatter->formatSection('Importing file', $file);
			$output->writeln($formattedLine);
			$affected = 0;
			foreach($queries as $idx => $singleQuery)
			{
				if (empty($singleQuery = trim($singleQuery)))
				{
					continue;
				}
				$output->writeln(sprintf("Executing statement %d", $idx + 1));
				$output->writeln($singleQuery);
				$affected = $this->container->dbConn->exec($singleQuery);
			}

			$output->writeln("Finished running $file, $affected rows affected");
		}

		$res = $this->container->dbConn->query("SELECT COUNT(DISTINCT table_name) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '" . Constants::DATABASE_NAME . "'")->fetch();
		$this->container->dbConn->exec("SET FOREIGN_KEY_CHECKS=1");
		$tableCount = $res['cnt'];
		$output->writeln("Database " . Constants::DATABASE_NAME . " now has $tableCount tables");


		return 0;
	}

	protected function checkAuroraDBAlreadyInit(OutputInterface $output)
	{
		//check to see if the TOE database exists, and if it does and has tables, print and exit

		$res = $this->container->dbConn->query("SHOW DATABASES");
		$hasTOE = false;
		foreach($res as $database)
		{
			if($database['Database'] === Constants::DATABASE_NAME)
			{
				$hasTOE = true;
			}
		}
		if($hasTOE)
		{
			//look for the tables
			$res = $this->container->dbConn->query("SELECT DISTINCT table_name FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '" . Constants::DATABASE_NAME . "'");
			if(!empty($res))
			{
				$output->writeln("'toe' database already has tables!");
				$output->writeln("Ensure the data is backed up and delete the schema with 'DROP DATABASE " . Constants::DATABASE_NAME . "'.");
				$output->writeln("Exiting...");

				return true;
			}
		}

		return false;
	}

	protected function resetDbConn()
	{
		$configs = $this->container->getDbConfigs();
		//for when connecting to regular mysql and you've wiped out the toe schema...
		if (isset($configs['dbname']))
		{
			unset($configs['dbname']);
		}
		$this->container->setNewConnection($configs);
	}

	protected function createAuroraSchema(string $schemaName)
	{
		$awsConfigs = AWSConfig::getStandardConfig();
		$awsConfigs['version'] = '2018-08-01';
		$aurora = (new AuroraDataAPIWrapper(new RDSDataServiceClient($awsConfigs)))
			->setDbArn(Env::get(Env::TOE_DB_ARN))
			->setSecretArn(Env::get(Env::TOE_DB_SECRET_ARN));
		$aurora->queryDB("CREATE SCHEMA $schemaName;");
	}
}