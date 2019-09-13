<?php
//phpinfo();
//die();
error_reporting(E_ALL);
//phpinfo();
//die();

require_once __DIR__ . '/../src/bootstrap.php';

use TOE\GlobalCode\clsEnv;

$app = new Silex\Application();

require __DIR__ . "/../src/app.php";
require __DIR__ . "/../config/config.php";
require __DIR__ . "/../config/routes.php";


$app['debug'] = clsEnv::Get(clsEnv::TOE_DEBUG_ON);
$app->run();

