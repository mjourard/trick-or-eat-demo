<?php
/* @var Application $app */

use Silex\Application;
use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsEnv;

$app['db.options'] = [
	'driver'   => 'pdo_mysql',
	'host'     => clsEnv::get(clsEnv::TOE_DATABASE_HOST),
	'port'     => clsEnv::get(clsEnv::TOE_DATABASE_PORT),
	'dbname'   => clsConstants::DATABASE_NAME,
	'charset'  => 'utf8',
	'user'     => clsEnv::get(clsEnv::TOE_DATABASE_USER),
	'password' => clsEnv::get(clsEnv::TOE_DATABASE_PASSWORD)
];

//anonymous routes are for when a user is not required to login
$app['routes.anonymous'] = [
	'checkTokenStatus',
	'countries',
	'login',
	'register',
	'regions',
	'requestReset',
	'resetPassword'
];

//$app['jwt.key'] = base64_decode("Sh9m44uV0J4a7Qy1BoWGkz2GuuophBEmuR11QXHLDXoPlTthoboP59Hp/BtXijicb1GWswCmOsQM5UdnmZ++7g==");
$app['jwt.key'] = base64_decode(clsEnv::get(clsEnv::TOE_ENCODED_JWT_KEY));

//parameters are how you populate the 'params' key in app. The key of the each element in the array should be the route defined in routes.php.
$app['parameters'] = [
	'checkTokenStatus'     => [
		'jwt' => clsConstants::SILEX_PARAM_STRING
	],
	'events/deregister'    => [
		'event_id' => clsConstants::SILEX_PARAM_INT
	],
	'events/register'      => [
		'event_id'  => clsConstants::SILEX_PARAM_INT,
		'can_drive' => clsConstants::SILEX_PARAM_BOOL,
		'mobility'  => clsConstants::SILEX_PARAM_BOOL,
		'visual'    => clsConstants::SILEX_PARAM_BOOL,
		'hearing'   => clsConstants::SILEX_PARAM_BOOL
	],
	'feedback/saveComment' => [
		'comment'     => clsConstants::SILEX_PARAM_STRING,
		'question_id' => clsConstants::SILEX_PARAM_INT
	],
	'login'                => [
		'password' => clsConstants::SILEX_PARAM_STRING,
		'email'    => clsConstants::SILEX_PARAM_STRING
	],
	'resetPassword'        => [
		'jwt'      => clsConstants::SILEX_PARAM_STRING,
		'password' => clsConstants::SILEX_PARAM_STRING
	],
	'requestReset'         => [
		'email' => clsConstants::SILEX_PARAM_STRING
	],
	'register'             => [
		'email'      => clsConstants::SILEX_PARAM_STRING,
		'password'   => clsConstants::SILEX_PARAM_STRING,
		'first_name' => clsConstants::SILEX_PARAM_STRING,
		'last_name'  => clsConstants::SILEX_PARAM_STRING,
		'region_id'  => clsConstants::SILEX_PARAM_INT
	],
	'routes/allocate'      => [
		'zoneId'  => clsConstants::SILEX_PARAM_INT,
		'routeId' => clsConstants::SILEX_PARAM_INT,
		'eventId' => clsConstants::SILEX_PARAM_INT
	],
	'routes/deallocate'    => [
		'routeId' => clsConstants::SILEX_PARAM_INT,
		'eventId' => clsConstants::SILEX_PARAM_INT
	],
	'team/create'          => [
		'Name'        => clsConstants::SILEX_PARAM_STRING,
		'memberCount' => clsConstants::SILEX_PARAM_INT,
		'join_code'   => clsConstants::SILEX_PARAM_STRING,
		"can_drive"   => clsConstants::SILEX_PARAM_BOOL,
		"visual"      => clsConstants::SILEX_PARAM_BOOL,
		"hearing"     => clsConstants::SILEX_PARAM_BOOL,
		"mobility"    => clsConstants::SILEX_PARAM_BOOL
	],
	'team/join'            => [
		'event_id'  => clsConstants::SILEX_PARAM_INT,
		'team_id'   => clsConstants::SILEX_PARAM_INT,
		'join_code' => clsConstants::SILEX_PARAM_STRING
	],
	'team/kick'            => [
		'teammate_id' => clsConstants::SILEX_PARAM_INT
	],
	'user/update'          => [
		'first_name' => clsConstants::SILEX_PARAM_STRING,
		'last_name'  => clsConstants::SILEX_PARAM_STRING,
		'region_id'  => clsConstants::SILEX_PARAM_INT
	],
	'zones/create'         => [
		'zone_name'               => clsConstants::SILEX_PARAM_STRING,
		'central_parking_address' => clsConstants::SILEX_PARAM_STRING,
		'central_building_name'   => clsConstants::SILEX_PARAM_STRING,
		'zone_radius_meter'       => clsConstants::SILEX_PARAM_INT,
		'houses_covered'          => clsConstants::SILEX_PARAM_INT,
		'zoom'                    => clsConstants::SILEX_PARAM_INT,
		'latitude'                => clsConstants::SILEX_PARAM_DOUBLE,
		'longitude'               => clsConstants::SILEX_PARAM_DOUBLE
	],
	'zones/edit'           => [
		'zone_id'                 => clsConstants::SILEX_PARAM_INT,
		'zone_name'               => clsConstants::SILEX_PARAM_STRING,
		'central_parking_address' => clsConstants::SILEX_PARAM_STRING,
		'central_building_name'   => clsConstants::SILEX_PARAM_STRING,
		'zone_radius_meter'       => clsConstants::SILEX_PARAM_INT,
		'houses_covered'          => clsConstants::SILEX_PARAM_INT,
		'zoom'                    => clsConstants::SILEX_PARAM_INT,
		'latitude'                => clsConstants::SILEX_PARAM_DOUBLE,
		'longitude'               => clsConstants::SILEX_PARAM_DOUBLE
	],
	'zones/routes'         => [
		'zone_id'  => clsConstants::SILEX_PARAM_STRING, //should be an _INT, the upload service is sending it as a String for some reason, even if you call parseInt before sending...
		'visual'   => clsConstants::SILEX_PARAM_STRING, //should be a _BOOL, the upload service is sending it as a String for some reason, even if you call parseInt before sending...
		'hearing'  => clsConstants::SILEX_PARAM_STRING, //should be a _BOOL, the upload service is sending it as a String for some reason, even if you call parseInt before sending...
		'mobility' => clsConstants::SILEX_PARAM_STRING, //should be a _BOOL, the upload service is sending it as a String for some reason, even if you call parseInt before sending...
		'type'     => clsConstants::SILEX_PARAM_STRING
	],
	'zones/status'         => [
		'zone_id' => clsConstants::SILEX_PARAM_INT,
		'status'  => clsConstants::SILEX_PARAM_STRING
	]
];

$app['redis.logging.ip'] = clsEnv::get(clsEnv::TOE_REDIS_LOGGING_IP);
$app['redis.logging.port'] = clsEnv::get(clsEnv::TOE_REDIS_LOGGING_PORT);
$app['redis.logging.password'] = clsEnv::get(clsEnv::TOE_REDIS_PASSWORD);

//This is here due to the order that $app is defined and executed in both production and development environments
if (getenv('dev_mode') === 'on')
{
	require __DIR__ . "/../config/config_dev.php";
}