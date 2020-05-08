#!/usr/bin/env php
<?php
declare(strict_types=1);

use Aws\RDSDataService\RDSDataServiceClient;
use Symfony\Component\Console\Application;
use TOE\App\DAL;
use TOE\App\Service\AWS\AuroraDataAPIWrapper;
use TOE\App\Service\AWSConfig;
use TOE\App\ServiceContainer;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\Env;

require __DIR__ . '/../vendor/autoload.php';

if(Env::get(Env::TOE_DONT_USE_DOTENV) !== 'true')
{
	$dotenv = new \Symfony\Component\Dotenv\Dotenv(true);
	$dotenv->loadEnv(__DIR__ . '/../.env');
}

$app = new Application();

$db = null;
$aurora = null;
switch(Env::get(Env::TOE_DATABASE_TYPE))
{
	case 'aurora':
		$awsConfigs = AWSConfig::getStandardConfig();
		$awsConfigs['version'] = '2018-08-01';
		$aurora = (new AuroraDataAPIWrapper(new RDSDataServiceClient($awsConfigs)))
			->setDbArn(Env::get(Env::TOE_DB_ARN))
			->setSecretArn(Env::get(Env::TOE_DB_SECRET_ARN))
			->setDatabase(Constants::DATABASE_NAME);
		break;
	case 'mysql':
	default:
		$db = new DAL(
			Env::get(Env::TOE_DATABASE_USER),
			Env::get(Env::TOE_DATABASE_PASSWORD),
			Env::get(Env::TOE_DATABASE_HOST),
			'',
			Env::get(Env::TOE_DATABASE_PORT)
		);
}
$container = new ServiceContainer($db, $aurora);

$filesToIgnore = ['.', '..', 'aCmd.php'];
$files = array_diff(scandir(__DIR__ . '/../src/App/Commands'), $filesToIgnore);

foreach($files as $file)
{
	$class = '\TOE\App\Commands\\' . trim($file, ".php");
	$app->add(new $class($container));
}

$app->run();