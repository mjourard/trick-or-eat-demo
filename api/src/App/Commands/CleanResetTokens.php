<?php


namespace TOE\App\Commands;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanResetTokens extends aCmd
{
	protected static $defaultName = 'clean-reset-tokens';

	protected function configure()
	{
		$this->setDescription('Deletes reset tokens that are used and/or old from the database');
		$this->addOption('clear-all-tokens', 'a', InputOption::VALUE_NONE, 'If provided, will clear out all reset tokens in the database, not just the expired ones');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->notifyUserOfDBType($output);
		//delete all expired tokens
		$query = "DELETE FROM password_request";
		$values = [];
		if (!$input->getOption('clear-all-tokens'))
		{
			//get the current time
			$now = new \DateTime('now', new \DateTimeZone('utc'));
			$values = [
				'dt' => $now->format('Y-m-d H:i:s')
			];
			$query .= "
			WHERE NOT unique_id = ''  
			AND (expired_at < :dt
			OR status = 'used')";
		}

		$output->writeln("Executing query: $query");
		$q = $this->container->dbConn->prepare($query);
		foreach($values as $key => $value)
		{
			$q->bindValue($key, $value);
		}
		$res = $q->execute();
		if (!$res)
		{
			$output->writeln("There was an error while trying to delete reset tokens from the datbase");
			$output->writeln(print_r($q->errorInfo(), true));
			return 1;
		}
		$output->writeln("Deleted $res from the database");
		return 0;
	}
}