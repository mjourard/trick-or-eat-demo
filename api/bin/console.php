#!/usr/bin/env php
<?php
use Symfony\Component\Console\Application;
use TOE\App\DAL;
use TOE\App\ServiceContainer;
use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsEnv;

require __DIR__ . '/../vendor/autoload.php';

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