<?php
//phpinfo();
//die();
error_reporting(E_ALL);
//phpinfo();
//die();

require_once __DIR__ . '/../src/bootstrap.php';

use TOE\GlobalCode\clsConstants;

$app = new Silex\Application();

require "../src/app.php";
require "../config/config.php";
require "../config/routes.php";


$app['debug'] = clsConstants::DEBUG_ON;
$app->run();

