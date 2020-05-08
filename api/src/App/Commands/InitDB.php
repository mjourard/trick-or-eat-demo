<?php
declare(strict_types=1);

namespace TOE\App\Commands;


use Aws\RDSDataService\Exception\RDSDataServiceException;
use Aws\S3\Exception\S3Exception;
use Silly\Input\InputArgument;
use Silly\Input\InputOption;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TOE\GlobalCode\Constants;

class InitDB extends aCmd
{
	protected static $defaultName = 'init-db';

	protected function configure()
	{
		$this->setDescription('Initializes the mysql database to be ready for use for trick-or-eat');
		$this->addArgument('querydir', InputArgument::OPTIONAL, 'A path to a directory that holds .sql files used to initialize the database. __DIR__ will be replaced by ' . __DIR__, __DIR__ . '/../../../../.docker/mysql/data');
		$this->addOption('aurora', 'aur', InputOption::VALUE_NONE, 'Use this when populating an aurora serverless database');
		$this->addOption('wipe', null, InputOption::VALUE_NONE, 'Use this to wipe the contents of the existing database if there is one');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$wipe = $input->getOption('wipe');
		if($input->getOption('aurora'))
		{
			return $this->initAurora($input, $output, $wipe);
		}
		else
		{
			return $this->initMysql($input, $output, $wipe);
		}
	}

	protected function initMysql(InputInterface $input, OutputInterface $output, $wipe)
	{
		//check to see if the TOE database exists, and if it does, print and exit
		$schemas = $this->container->db->query("SHOW DATABASES");

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
				$tables = $this->container->db->query("SELECT DISTINCT table_name FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '" . Constants::DATABASE_NAME . "'");
				if (!empty($tables))
				{
					$output->writeln("'toe' database already exists!");
					$output->writeln("Ensure the data is backed up and delete the schema with 'DROP DATABASE " . Constants::DATABASE_NAME . "'.");
					$output->writeln("Exiting...");

					return 1;
				}
			}
		}

		$dir = $input->getArgument('querydir');
		$dir = str_replace('__DIR__', __DIR__, $dir);
		$dir = rtrim($dir, "/");
		//run the mysql dump commands
		$files = array_diff(scandir($dir), ['.', '..']);
		foreach($files as $file)
		{
			$query = file_get_contents($file);
			$affected = $this->container->db->rawExecuteNonQuery($query);
			$output->writeln("Finished running $file, $affected rows affected");
		}

		$res = $this->container->db->query("SELECT COUNT(DISTINCT table_name) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '" . Constants::DATABASE_NAME . "'");
		$tableCount = $res[0]['cnt'];
		$output->writeln("Database " . Constants::DATABASE_NAME . " now has $tableCount tables");


		return 0;
	}

	protected function initAurora(InputInterface $input, OutputInterface $output, $wipe)
	{
		//TODO: implement the wipe option to reset the database for tests
		if ($this->checkAuroraDBAlreadyInit($output) !== 0)
		{
			return 1;
		}

		$dir = $input->getArgument('querydir');
		$dir = str_replace('__DIR__', __DIR__, $dir);
		$dir = rtrim($dir, "/");
		//run the mysql dump commands
		$files = array_diff(scandir($dir), ['.', '..']);
		if (empty($files))
		{
			$output->writeln("Unable to find any files to initialize the database with");
			return 1;
		}
		/** @var FormatterHelper $formatter */
		$formatter = $this->getHelper('formatter');
		$this->container->aurora->executeStatement("SET FOREIGN_KEY_CHECKS=0");
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
				$res = $this->container->aurora->executeStatement($singleQuery);
				$affected += $res->get('numberOfRecordsUpdated');
			}

			$output->writeln("Finished running $file, $affected rows affected");
		}
		$this->container->aurora->executeStatement("SET FOREIGN_KEY_CHECKS=1");

		return 0;
	}

	protected function checkAuroraDBAlreadyInit(OutputInterface $output)
	{
		//check to see if the TOE database exists, and if it does and has tables, print and exit
		try
		{
			$res = $this->container->aurora->executeStatement("SHOW DATABASES");
		}
		catch(RDSDataServiceException $ex)
		{
			if(stripos($ex->getMessage(), "The last packet sent successfully to the server was 0 milliseconds ago. The driver has not recieved any packets from the server") !== false)
			{
				$output->writeln("Aurora Serverless might be warming up. Check the current capacity size of the cluster");

				return 1;
			}
			else
			{
				throw $ex;
			}
		}
		$schemas = $res->get('records');

		$hasTOE = false;
		foreach($schemas as $schemaRow)
		{
			if($schemaRow[0]['stringValue'] === Constants::DATABASE_NAME)
			{
				$hasTOE = true;
			}
		}
		if($hasTOE)
		{
			//look for the tables
			$res = $this->container->aurora->executeStatement("SELECT DISTINCT table_name FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '" . Constants::DATABASE_NAME . "'");
			$tables = $res->get('records');
			if(!empty($tables))
			{
				$output->writeln("'toe' database already has tables!");
				$output->writeln("Ensure the data is backed up and delete the schema with 'DROP DATABASE " . Constants::DATABASE_NAME . "'.");
				$output->writeln("Exiting...");

				//TODO: re-enable
//				return 1;
			}
		}

		return 0;
	}
}