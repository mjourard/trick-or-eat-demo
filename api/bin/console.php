#!/usr/bin/env php
<?php
declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Monolog\Logger;
use TOE\App\ServiceContainer;
use TOE\GlobalCode\Env;

require __DIR__ . '/../vendor/autoload.php';

if(Env::get(Env::TOE_DONT_USE_DOTENV) !== 'true')
{
	$dotenv = new \Symfony\Component\Dotenv\Dotenv(true);
	$dotenv->loadEnv(__DIR__ . '/../.env');
}

//do this because we are currently combining silex and symfony mid migration and want to use the configs of the main app
$app = [];
require __DIR__ . '/../config/config.php';

$console = new Symfony\Component\Console\Application();

$conn = DriverManager::getConnection($app['db.options']);
$log = new Logger('console');
$log->pushHandler(new \Monolog\Handler\StreamHandler(STDOUT, Logger::NOTICE));
$container = new ServiceContainer($conn, $log, $app);

$filesToIgnore = ['.', '..', 'aCmd.php'];
$files = array_diff(scandir(__DIR__ . '/../src/App/Commands'), $filesToIgnore);

foreach($files as $file)
{
	$class = '\TOE\App\Commands\\' . trim($file, ".php");
	$console->add(new $class($container));
}

$console->run();