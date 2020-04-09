<?php
error_reporting(E_ALL);

require_once __DIR__ . '/../src/bootstrap.php';

use TOE\GlobalCode\clsEnv;

$app = new Silex\Application();

require __DIR__ . "/../src/app.php";
require __DIR__ . "/../config/config.php";
require __DIR__ . "/../config/routes.php";


$app['debug'] = clsEnv::get(clsEnv::TOE_DEBUG_ON);
$app->run();

