<?php
declare(strict_types=1);

namespace TOE\App\Commands;


use Aws\RDSDataService\Exception\RDSDataServiceException;
use Silly\Input\InputArgument;
use Silly\Input\InputOption;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AurQuery extends aCmd
{
	protected static $defaultName = 'aur-query';

	protected function configure()
	{
		$this->setDescription('Runs the specified query or query file against the aurora database and outputs the results. Created because querying an aurora serverless database without a VPN into the VPC is annoying');
		$this->addArgument('query', InputArgument::REQUIRED, 'A MySQL-compatible query. Pass in a filepath if the --file flag is used');
		$this->addOption('file', 'f', InputOption::VALUE_NONE, 'Use this you want to read in a file containing mysql query or queries to be executed');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->notifyUserOfDBType($output);
		$query = $input->getArgument('query');
		if ($input->getOption('file'))
		{
			switch(substr($query, 0, 1))
			{
				case '/':
					$path = $query;
					break;
				case '.':
					if (substr($query, 0, 2) == '..')
					{
						//add current path
						$path = getcwd() . DIRECTORY_SEPARATOR . $query;
					}
					else
					{
						//replace first character with current path
						$path = getcwd() . DIRECTORY_SEPARATOR . substr($query, 2);
					}
					break;
				default:
					$path = getcwd() . DIRECTORY_SEPARATOR . $query;
			}
			$query = file_get_contents($path);
		}

		try
		{
			return $this->runAuroraQuery($output, $query);
		}
		catch(RDSDataServiceException $ex)
		{
			if (stripos($ex->getMessage(), "The last packet sent successfully to the server was 0 milliseconds ago") !== false)
			{
				$output->writeln("The database is initializing. Check back momentarily");
				return 1;
			}
			else
			{
				throw $ex;
			}
		}

	}

	protected function runAuroraQuery(OutputInterface $output, $multiQuery)
	{
		/** @var FormatterHelper $formatter */
		$formatter = $this->getHelper('formatter');
		$queries = explode(";", $multiQuery);
		foreach($queries as $idx => $query)
		{
			//remove all the comments generated from mysqldump
			$query = preg_replace('|/\*.*\*/;|', '', $query);
			$query = trim($query);
			if (empty($query))
			{
				$output->writeln(sprintf("After removing comments and whitespace, the single query was empty. Skipping query #%d", $idx + 1));
				continue;
			}

			$formattedLine = $formatter->formatSection(sprintf('Query #%d', $idx + 1), '');
			$output->writeln($formattedLine);

			$output->writeln($query);
			$q = $this->container->dbConn->prepare($query);
			if (!$q->execute())
			{
				$output->writeln("error while executing sql statement: ");
				$output->writeln(print_r($q->errorInfo(), true));
				return 1;
			}
			$res = $q->fetchAll();
			$table = new Table($output);
			$table->setHeaders(array_keys($res[0]));
			$rows = [];
			foreach($res as $row)
			{
				foreach($row as &$val)
				{
					if (is_string($val))
					{
						$val = "'" . $val .  "'";
					}
					if ($val === null)
					{
						$val = 'NULL';
					}
				}
				$rows[] = array_values($row);
			}
			$table->setRows($rows);
			$table->render();
		}

		return 0;
	}
}