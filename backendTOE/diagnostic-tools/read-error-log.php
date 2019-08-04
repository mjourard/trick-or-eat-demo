<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 10/31/2017
 * Time: 4:53 PM
 */

use TOE\Creds\clsCreds;
use TOE\GlobalCode\clsConstants;

require __DIR__ . '/../vendor/autoload.php';

const ERRORS_KEY = 'dev-errors';

$errorCount = 10;
if (count($argv) < 2)
{
	echo "Usage: " . __FILE__ . " <number-of-errors> \nDefaulting to $errorCount\n\n\n";
}
else
{
	$eerrorCount = $argv[1];
}

//die("[2017-10-31 20:48:17] app.DEBUG: < 200 [] {\"url\":\"/backendtoe/public/index.php/regions/1\",\"ip\":\"10.11.220.215\",\"http_method\":\"GET\",\"server\":\"guelphtrickoreat.ca\",\"referrer\":\"https://guelphtrickoreat.ca/\"}\n");

$redis = new Redis();
$redis->connect('127.0.0.1', clsConstants::REDIS_LOGGING_PORT);
$redis->auth(clsCreds::REDIS_PASSWORD);
$errors = $redis->lRange(ERRORS_KEY, $errorCount, -1);
foreach ($errors as $error)
{
	echo $error . "\n";
}

