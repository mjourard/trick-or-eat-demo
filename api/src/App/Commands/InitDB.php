<?php


namespace TOE\App\Commands;


use Silly\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TOE\GlobalCode\clsConstants;

class InitDB extends aCmd
{
	protected static $defaultName = 'init-db';

	protected function configure()
	{
		$this->setDescription('Initializes the mysql database to be ready for use for trick-or-eat');
		$this->addArgument('querydir', InputArgument::OPTIONAL, 'A path to a directory that holds .sql files used to initialize the database. __DIR__ will be replaced by ' . __DIR__, __DIR__ . '/../../../../.docker/mysql/data');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		//check to see if the TOE database exists, and if it does, print and exit
		$schemas = $this->container->db->Query("SHOW DATABASES");

		if (!empty($schemas))
		{
			$hasTOE = false;
			foreach($schemas as $schemaRow)
			{
				if ($schemaRow['Database'] === clsConstants::DATABASE_NAME)
				{
					$hasTOE = true;
				}
			}
			if ($hasTOE)
			{
				$tables = $this->container->db->Query("SELECT DISTINCT table_name FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '" . clsConstants::DATABASE_NAME . "'");
				if (!empty($tables))
				{
					$output->writeln("'toe' database already exists!");
					$output->writeln("Ensure the data is backed up and delete the schema with 'DROP DATABASE " . clsConstants::DATABASE_NAME . "'.");
					$output->writeln("Exiting...");

					return 1;
				}
			}
		}

		$dir = $input->getArgument('querydir');
		$dir = str_replace('__DIR__', __DIR__, $dir);
		$dir = rtrim($dir, "/");
		//run the mysql dump commands
		$files = [
			"$dir/100_config_toe.sql",
			"$dir/200_data_toe.sql"
		];
		foreach($files as $file)
		{
			$query = file_get_contents($file);
			$affected = $this->container->db->RawExecuteNonQuery($query);
			$output->writeln("Finished running $file, $affected rows affected");
		}

		$res = $this->container->db->Query("SELECT COUNT(DISTINCT table_name) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = '" . clsConstants::DATABASE_NAME . "'");
		$tableCount = $res[0]['cnt'];
		$output->writeln("Database " . clsConstants::DATABASE_NAME . " now has $tableCount tables");

		return 0;
	}
}