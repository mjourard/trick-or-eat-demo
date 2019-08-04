<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 7/9/2017
 * Time: 3:33 PM
 */

$app['db.options'] = [
	'driver'   => 'pdo_mysql',
	'host'     => getenv('USER_DATABASE_HOST'),
	'port'     => '3306',
	'dbname'   => TOE\GlobalCode\clsConstants::DATABASE_NAME,
	'charset'  => 'utf8',
	'user'     => getenv('USER_DATABASE_USERNAME'),
	'password' => getenv('USER_DATABASE_PASSWORD')
];


/* @var \Silex\Application $app */
$app['redis.logging.ip'] = getenv('REDIS_LOGGING_IP');
$app['redis.logging.port'] = getenv('REDIS_LOGGING_PORT');
$app['redis.logging.password'] = getenv('REDIS_LOGGING_PASSWORD');;
