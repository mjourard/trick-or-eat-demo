<?php
declare(strict_types=1);

use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\WebProcessor;
use Symfony\Component\HttpFoundation\Request;
use \Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Response;
use TOE\App\Service\Bus\BusServiceProvider;
use TOE\App\Service\Email\EmailServiceProvider;
use TOE\App\Service\Event\EventServiceProvider;
use TOE\App\Service\Feedback\FeedbackServiceProvider;
use TOE\App\Service\Location\LocationServiceProvider;
use TOE\App\Service\ParameterVerifier;
use TOE\App\Service\Password\PasswordRequestServiceProvider;
use TOE\App\Service\Route\Archive\RouteServiceProvider;
use TOE\App\Service\Route\Assignment\AssignmentServiceProvider;
use TOE\App\Service\Team\TeamServiceProvider;
use TOE\App\Service\User\UserInfoStorage;
use TOE\App\Service\User\UserServiceProvider;
use TOE\GlobalCode\Env;
use TOE\GlobalCode\HTTPCodes;
use TOE\GlobalCode\ResponseJson;

$logFile = Env::get(Env::TOE_LOG_FILE);
if (empty($logFile))
{
	$logFile = __DIR__ . '/../logs/error.log';
}
$level = Logger::WARNING;
if (isset(Logger::getLevels()[Env::get(Env::TOE_LOGGING_LEVEL)]))
{
	$level = Logger::toMonologLevel(Env::get(Env::TOE_LOGGING_LEVEL));
}
/* @var \Silex\Application $app */
$app->register(new Silex\Provider\DoctrineServiceProvider());
$app->register(new Silex\Provider\ServiceControllerServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\MonologServiceProvider(), [
	'monolog.logfile' => $logFile,
	'monolog.bubble'  => true,
	'monolog.level'   => $level
]);
$app->extend('monolog', function (Logger $monolog, $app)
{
	//Adds the current request URI, request method and client IP to a log record.
	$monolog->pushProcessor(new WebProcessor());
	$monolog->pushProcessor(new IntrospectionProcessor());

	return $monolog;
});

// app specific services
$app->register(new AssignmentServiceProvider());
$app->register(new BusServiceProvider());
$app->register(new EventServiceProvider());
$app->register(new FeedbackServiceProvider());
$app->register(new LocationServiceProvider());
$app->register(new PasswordRequestServiceProvider());
$app->register(new RouteServiceProvider());
$app->register(new TeamServiceProvider());
$app->register(new UserServiceProvider());
$app->register(new EmailServiceProvider());


$app->before(function (Request $request) use ($app)
{
	if (!empty($request->getContent()))
	{
		$contentType = 'application/json';
		if (strcmp($request->headers->get('Content-Type'), $contentType) !== 0)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "Content-Type request header did not match required value of '$contentType'. Received: '{$request->headers->get('Content-Type')}'"), HTTPCodes::CLI_ERR_BAD_REQUEST);
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
			return $app->json(ResponseJson::getJsonResponseArray(false, 'Not logged in (no auth header).'), HTTPCodes::CLI_ERR_AUTH_REQUIRED);
		}
		/* if the program stays in this try{}
		 * it means that the user has logged in OK
		 * their request is getting processed, starting with param verification
		 */
		try
		{
			$decToken = JWT::decode($token, $app['jwt.key'], ['HS512']);
			/* @var $userInfo UserInfoStorage */
			$userInfo = $app['user_info'];
			$userInfo->setToken($decToken);
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

	/**
	 * @return ParameterVerifier
	 */
	$app['param.verifier'] = $app->factory(function($app) {
		return new ParameterVerifier($app['parameters']);
	});

	$results = $app['param.verifier']->verify($request);
	if (array_key_exists('success', $results))
	{
		return $app->json($results, HTTPCodes::CLI_ERR_BAD_REQUEST);
	}
	$app['params'] = $results;
});
$app->after(function(Request $request, Response $response) use ($app)
{
	$response->headers->set('Access-Control-Allow-Origin', Env::get(Env::TOE_ACCESS_CONTROL_ALLOW_ORIGIN), true);
	$response->headers->set("Vary", "Origin");
	$response->headers->set('Access-Control-Allow-Credentials', 'true', true);
});
