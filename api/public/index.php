<?php
error_reporting(E_ALL);

require_once __DIR__ . '/../src/bootstrap.php';

use TOE\GlobalCode\Env;

$app = new Silex\Application();

require __DIR__ . "/../src/app.php";
require __DIR__ . "/../config/config.php";
require __DIR__ . "/../config/routes.php";


$app['debug'] = Env::get(Env::TOE_DEBUG_ON);
$app->run();

