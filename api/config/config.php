<?php
declare(strict_types=1);

/* @var Application $app */

use Silex\Application;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\Env;

$app['db.type'] = Env::get(Env::TOE_DATABASE_TYPE);
$app['db.options'] = [
	'driver'   => 'pdo_mysql',
	'host'     => Env::get(Env::TOE_DATABASE_HOST),
	'port'     => Env::get(Env::TOE_DATABASE_PORT),
	'dbname'   => Constants::DATABASE_NAME,
	'charset'  => 'utf8',
	'user'     => Env::get(Env::TOE_DATABASE_USER),
	'password' => Env::get(Env::TOE_DATABASE_PASSWORD)
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
$app['jwt.key'] = base64_decode(Env::get(Env::TOE_ENCODED_JWT_KEY));

//parameters are how you populate the 'params' key in app. The key of the each element in the array should be the route defined in routes.php.
$app['parameters'] = [
	'checkTokenStatus'     => [
		'jwt' => Constants::SILEX_PARAM_STRING
	],
	'events/deregister'    => [
		'event_id' => Constants::SILEX_PARAM_INT
	],
	'events/register'      => [
		'event_id'  => Constants::SILEX_PARAM_INT,
		'can_drive' => Constants::SILEX_PARAM_BOOL,
		'mobility'  => Constants::SILEX_PARAM_BOOL,
		'visual'    => Constants::SILEX_PARAM_BOOL,
		'hearing'   => Constants::SILEX_PARAM_BOOL
	],
	'feedback/saveComment' => [
		'comment'     => Constants::SILEX_PARAM_STRING,
		'question_id' => Constants::SILEX_PARAM_INT
	],
	'login'                => [
		'password' => Constants::SILEX_PARAM_STRING,
		'email'    => Constants::SILEX_PARAM_STRING
	],
	'resetPassword'        => [
		'jwt'      => Constants::SILEX_PARAM_STRING,
		'password' => Constants::SILEX_PARAM_STRING
	],
	'requestReset'         => [
		'email' => Constants::SILEX_PARAM_STRING
	],
	'register'             => [
		'email'      => Constants::SILEX_PARAM_STRING,
		'password'   => Constants::SILEX_PARAM_STRING,
		'first_name' => Constants::SILEX_PARAM_STRING,
		'last_name'  => Constants::SILEX_PARAM_STRING,
		'region_id'  => Constants::SILEX_PARAM_INT
	],
	'routes/allocate'      => [
		'zoneId'  => Constants::SILEX_PARAM_INT,
		'routeId' => Constants::SILEX_PARAM_INT,
		'eventId' => Constants::SILEX_PARAM_INT
	],
	'routes/deallocate'    => [
		'routeId' => Constants::SILEX_PARAM_INT,
		'eventId' => Constants::SILEX_PARAM_INT
	],
	'team/create'          => [
		'Name'        => Constants::SILEX_PARAM_STRING,
		'memberCount' => Constants::SILEX_PARAM_INT,
		'join_code'   => Constants::SILEX_PARAM_STRING,
		"can_drive"   => Constants::SILEX_PARAM_BOOL,
		"visual"      => Constants::SILEX_PARAM_BOOL,
		"hearing"     => Constants::SILEX_PARAM_BOOL,
		"mobility"    => Constants::SILEX_PARAM_BOOL
	],
	'team/join'            => [
		'event_id'  => Constants::SILEX_PARAM_INT,
		'team_id'   => Constants::SILEX_PARAM_INT,
		'join_code' => Constants::SILEX_PARAM_STRING
	],
	'team/kick'            => [
		'team_id'     => Constants::SILEX_PARAM_INT,
		'teammate_id' => Constants::SILEX_PARAM_INT
	],
	'user/update'          => [
		'first_name' => Constants::SILEX_PARAM_STRING,
		'last_name'  => Constants::SILEX_PARAM_STRING,
		'region_id'  => Constants::SILEX_PARAM_INT
	],
	'zones/create'         => [
		'zone_name'               => Constants::SILEX_PARAM_STRING,
		'central_parking_address' => Constants::SILEX_PARAM_STRING,
		'central_building_name'   => Constants::SILEX_PARAM_STRING,
		'zone_radius_meter'       => Constants::SILEX_PARAM_INT,
		'houses_covered'          => Constants::SILEX_PARAM_INT,
		'zoom'                    => Constants::SILEX_PARAM_INT,
		'latitude'                => Constants::SILEX_PARAM_DOUBLE,
		'longitude'               => Constants::SILEX_PARAM_DOUBLE
	],
	'zones/edit'           => [
		'zone_id'                 => Constants::SILEX_PARAM_INT,
		'zone_name'               => Constants::SILEX_PARAM_STRING,
		'central_parking_address' => Constants::SILEX_PARAM_STRING,
		'central_building_name'   => Constants::SILEX_PARAM_STRING,
		'zone_radius_meter'       => Constants::SILEX_PARAM_INT,
		'houses_covered'          => Constants::SILEX_PARAM_INT,
		'zoom'                    => Constants::SILEX_PARAM_INT,
		'latitude'                => Constants::SILEX_PARAM_DOUBLE,
		'longitude'               => Constants::SILEX_PARAM_DOUBLE
	],
	'zones/routes'         => [
		'zone_id'  => Constants::SILEX_PARAM_STRING, //should be an _INT, the upload service is sending it as a String for some reason, even if you call parseInt before sending...
		'visual'   => Constants::SILEX_PARAM_STRING, //should be a _BOOL, the upload service is sending it as a String for some reason, even if you call parseInt before sending...
		'hearing'  => Constants::SILEX_PARAM_STRING, //should be a _BOOL, the upload service is sending it as a String for some reason, even if you call parseInt before sending...
		'mobility' => Constants::SILEX_PARAM_STRING, //should be a _BOOL, the upload service is sending it as a String for some reason, even if you call parseInt before sending...
		'type'     => Constants::SILEX_PARAM_STRING
	],
	'zones/status'         => [
		'zone_id' => Constants::SILEX_PARAM_INT,
		'status'  => Constants::SILEX_PARAM_STRING
	]
];