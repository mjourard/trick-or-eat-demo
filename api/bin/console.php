#!/usr/bin/env php
<?php
declare(strict_types=1);

use Symfony\Component\Console\Application;
use TOE\App\DAL;
use TOE\App\ServiceContainer;
use TOE\GlobalCode\clsEnv;

require __DIR__ . '/../vendor/autoload.php';

if (clsEnv::get(clsEnv::TOE_DONT_USE_DOTENV) !== 'true')
{
	$dotenv = new \Symfony\Component\Dotenv\Dotenv(true);
	$dotenv->loadEnv(__DIR__ . '/../.env');
}

$app = new Application();

$db = new DAL(
	clsEnv::get(clsEnv::TOE_DATABASE_USER),
	clsEnv::get(clsEnv::TOE_DATABASE_PASSWORD),
	clsEnv::get(clsEnv::TOE_DATABASE_HOST),
	'',
	clsEnv::get(clsEnv::TOE_DATABASE_PORT)
);
$container = new ServiceContainer($db);

$filesToIgnore = ['.', '..', 'aCmd.php'];
$files = array_diff(scandir(__DIR__ . '/../src/App/Commands'), $filesToIgnore);

foreach($files as $file)
{
    $class = '\TOE\App\Commands\\' . trim($file, ".php");
    $app->add(new $class($container));
}

$app->run();