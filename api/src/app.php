<?php

use Monolog\Handler\RedisHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Symfony\Component\HttpFoundation\Request;
use \Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Response;
use TOE\App\Service\Email\ClientFactory;
use TOE\App\Service\ParameterVerifier;
use TOE\App\Service\UserServiceProvider;
use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsEnv;
use TOE\GlobalCode\clsHTTPCodes;
use TOE\GlobalCode\clsResponseJson;

$logFile = clsEnv::get(clsEnv::TOE_LOG_FILE);
if (empty($logFile))
{
	$logFile = __DIR__ . '/../logs/error.log';
}
$level = Logger::WARNING;
if (isset(Logger::getLevels()[clsEnv::get(clsEnv::TOE_LOGGING_LEVEL)]))
{
	$level = Logger::toMonologLevel(clsEnv::get(clsEnv::TOE_LOGGING_LEVEL));
}
/* @var \Silex\Application $app */
$app->register(new Silex\Provider\DoctrineServiceProvider());
$app->register(new Silex\Provider\ServiceControllerServiceProvider());
$app->register(new UserServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\MonologServiceProvider(), [
	'monolog.logfile' => $logFile,
	'monolog.bubble'  => true,
	'monolog.level'   => $level
]);
$app->extend('monolog', function (Logger $monolog, $app)
{
	$params = [
		'scheme'   => 'tcp',
		'host'     => $app['redis.logging.ip'],
		'port'     => $app['redis.logging.port']
	];
	if (!empty($app['redis.logging.password']))
	{
		$params['password'] = $app['redis.logging.password'];
	}
	$redis = new Predis\Client($params);
	$key = clsEnv::get(clsEnv::TOE_DEBUG_ON) ? "dev-" : "";
	$key .= clsConstants::REDIS_ERROR_KEY;
	$monolog->pushHandler(new RedisHandler($redis, $key));

	//Adds the current request URI, request method and client IP to a log record.
	$monolog->pushProcessor(new WebProcessor());
	$monolog->pushProcessor(new IntrospectionProcessor());

	return $monolog;
});

$app['user.lookup'] = function($app) {
	return new TOE\App\Service\UserLookupService($app['db']);
};

$app['email'] = function() {
	return ClientFactory::getClient();
};

$app->before(function (Request $request) use ($app)
{
	if (!empty($request->getContent()))
	{
		$contentType = 'application/json';
		if (strcmp($request->headers->get('Content-Type'), $contentType) !== 0)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Content-Type request header did not match required value of '$contentType'. Received: '{$request->headers->get('Content-Type')}'"), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		$data = json_decode($request->getContent(), true);
		$request->request->replace(is_array($data) ? $data : []);
	}
	$request_route = $request->get("_route"); // ex. POST_login
	/* build regex to look for anonymous routes which says:
	 * 'route will start with 'GET' or 'POST' and an underscore will follow'
	 * final regex looks like
	 * /^(GET|POST)_(anonRoute1|anonRoute2|anonRoute3)/
	 */
	$regex = '/^(GET|POST)_(' . implode("|", $app['routes.anonymous']) . ')/';
	/* look for current route in anonymous routes
	 * "if current route not in anonymous routes, abort"
	 */
	if (!preg_match($regex, $request_route, $matched_route))
	{
		$token = $request->headers->get('X-Bearer-Token');
		if (!$token)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, 'Not logged in (no auth header).'), clsHTTPCodes::CLI_ERR_AUTH_REQUIRED);
		};
		/* if the program stays in this try{}
		 * it means that the user has logged in OK
		 * their request is getting processed, starting with param verification
		 */
		try
		{
			$decToken = JWT::decode($token, $app['jwt.key'], ['HS512']);
			/* @var $app['user_info'] \App\Service\UserInfoStorage */
			$app['user_info']->SetToken($decToken);
		}
		catch (Exception $e)
		{
			return $app->json([
				'success' => false,
				'message' => 'Invalid token.',
				'more'    => $e->getMessage() . $e->getTraceAsString()
			], 401);
		}
	}

	//The if (isset()) is for making the functional tests with phpunit and webtestcase work. PHPunit doesn't allow it to return the same service (freezes it).

	if (!isset($app['param.verifier']))
	{
		/**
		 * @return ParameterVerifier
		 */
		$app['param.verifier'] = function () use ($app)
		{
			return new ParameterVerifier($app['parameters']);
		};
	}

	$results = $app['param.verifier']->verify($request);
	if (array_key_exists('success', $results))
	{
		return $app->json($results, clsHTTPCodes::CLI_ERR_BAD_REQUEST);
	};
	$app['params'] = $results;
});
$app->after(function(Request $request, Response $response) use ($app)
{
	$response->headers->set('Access-Control-Allow-Origin', clsEnv::get(clsEnv::TOE_ACCESS_CONTROL_ALLOW_ORIGIN), true);
	$response->headers->set("Vary", "Origin");
	$response->headers->set('Access-Control-Allow-Credentials', 'true', true);
});
