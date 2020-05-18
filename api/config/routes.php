<?php
declare(strict_types=1);

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\Env;
use TOE\GlobalCode\ResponseJson;
use TOE\GlobalCode\Util;

$strtoint = function ($id)
{
	return (int)$id;
};

/* @var Application $app */
$app->post('/login', 'TOE\App\Controller\AuthController::login');
$app->post('/register', 'TOE\App\Controller\AuthController::register');

$app->post('/requestReset', 'TOE\App\Controller\RequestResetController::requestReset');
$app->get('/checkTokenStatus/{token}', 'TOE\App\Controller\ResetPasswordController::checkTokenStatus');
$app->post('/resetPassword', 'TOE\App\Controller\ResetPasswordController::resetPassword');

$app->get('/countries', 'TOE\App\Controller\RegionController::getCountries');
$app->get('/regions/{countryId}', 'TOE\App\Controller\RegionController::getRegion')
	->assert('countryId', Constants::STANDARD_ID_REGEX);

$app->get('/team/team', 'TOE\App\Controller\TeamController::getTeam');
$app->get('/team/teams', 'TOE\App\Controller\TeamController::getTeams');
$app->get('/team/exists/{teamName}', 'TOE\App\Controller\TeamController::isTeamNameAvailable')
	->assert('teamName', Constants::STANDARD_NOT_WHITESPACE_REGEX);
$app->post('/team/join', 'TOE\App\Controller\TeamController::joinTeam');
$app->post('/team/assignRoute', 'TOE\App\Controller\TeamController::assignRoute');
$app->post('/team/create', 'TOE\App\Controller\TeamController::createTeam');
$app->post('/team/kick', 'TOE\App\Controller\TeamController::kickTeammate');
$app->post('/team/leave', 'TOE\App\Controller\TeamController::leaveTeam');

$app->post('/events/register', 'TOE\App\Controller\EventController::register');
$app->post('/events/deregister', 'TOE\App\Controller\EventController::deregister');
$app->get('/events/{regionId}', 'TOE\App\Controller\EventController::getEvents')
	->assert('regionId', Constants::STANDARD_ID_REGEX)
	->convert('regionId', $strtoint);

$app->get('/user/userInfo', 'TOE\App\Controller\UserController::getUserInfo');
$app->put('/user/update', 'TOE\App\Controller\UserController::updateUserInfo');

$app->get('/routes/{eventId}', 'TOE\App\Controller\RouteController::getRoutesForEvent')
	->assert('eventId', Constants::STANDARD_ID_REGEX)
	->convert('eventId', $strtoint);
$app->get('/routes/unallocated/{eventId}', 'TOE\App\Controller\RouteController::getUnallocatedRoutes')
	->assert('eventId', Constants::STANDARD_ID_REGEX)
	->convert('eventId', $strtoint);
$app->post('/routes/allocate', 'TOE\App\Controller\RouteController::allocateRoute');
$app->delete('/routes/deallocate', 'TOE\App\Controller\RouteController::deallocateRoute');
$app->get('/routes/{eventId}/getRouteAssignments/orderBy/{orderBy}', 'TOE\App\Controller\RouteController::getRouteAssignments')
	->assert('eventId', Constants::STANDARD_ID_REGEX)
	->convert('eventId', $strtoint);

$app->get('/routes/{eventId}/getRouteAssignments/{teamId}', 'TOE\App\Controller\RouteController::getTeamRouteAssignments')
	->assert('eventId', Constants::STANDARD_ID_REGEX)
	->assert('teamId', Constants::STANDARD_ID_REGEX)
	->convert('eventId', $strtoint)
	->convert('teamId', $strtoint);

$app->put('/routes/{eventId}/assignAllRoutes', 'TOE\App\Controller\RouteController::assignAllRoutes')
	->assert('eventId', Constants::STANDARD_ID_REGEX)
	->convert('eventId', $strtoint);

$app->put('/routes/{eventId}/removeAllRouteAssignments', 'TOE\App\Controller\RouteController::removeAllRouteAssignments')
	->assert('eventId', Constants::STANDARD_ID_REGEX)
	->convert('eventId', $strtoint);

$app->post('/zones/create', 'TOE\App\Controller\ZoneController::createZone');
$app->put('/zones/edit', 'TOE\App\Controller\ZoneController::editZone');
$app->get('/zones/{regionId}/{status}', 'TOE\App\Controller\ZoneController::getZones')
	->assert('regionId', Constants::STANDARD_ID_REGEX)
	->convert('regionId', $strtoint);
$app->get('/zones/details/{zoneId}', 'TOE\App\Controller\ZoneController::getZone')
	->assert('zoneId', Constants::STANDARD_ID_REGEX)
	->convert('zoneId', $strtoint);
$app->put('/zones/status', 'TOE\App\Controller\ZoneController::setZoneStatus')
	->assert('zoneId', Constants::STANDARD_ID_REGEX)
	->convert('zoneId', $strtoint);

$app->get('/zones/routes/{zoneId}', 'TOE\App\Controller\RouteArchiveController::getRoutes')
	->assert('zoneId', Constants::STANDARD_ID_REGEX)
	->convert('zoneId', $strtoint);
$app->get('/zones/routes/{zoneId}/mapdetails', 'TOE\App\Controller\RouteArchiveController::getRouteDetails')
	->assert('zoneId', Constants::STANDARD_ID_REGEX)
	->convert('zoneId', $strtoint);
$app->delete('/zones/routes/{zoneId}/{routeId}', 'TOE\App\Controller\RouteArchiveController::deleteRoute')
	->assert('zoneId', Constants::STANDARD_ID_REGEX)
	->assert('routeId', Constants::STANDARD_ID_REGEX)
	->convert('zoneId', $strtoint)
	->convert('routeId', $strtoint);
$app->post('/zones/routes', 'TOE\App\Controller\RouteArchiveController::addRoute');

$app->get('/feedback/getquestions', 'TOE\App\Controller\FeedbackController::getQuestions');
$app->post('/feedback/saveComment', 'TOE\App\Controller\FeedbackController::saveComment');
$app->get('/feedback/comment/{questionId}', 'TOE\App\Controller\FeedbackController::getComment')
	->assert('questionId', Constants::STANDARD_ID_REGEX)
	->convert('questionId', $strtoint);
$app->get('/feedback/comment/maxCharacterCount', 'TOE\App\Controller\FeedbackController::getCharacterLimit');

$app->get('/health/siteissues', 'TOE\App\Controller\SiteHealthController::getSiteIssues');

//This is the catch-all error handler. All uncaught errors and exceptions are sent here.
$app->error(function (\Exception $e, Request $request, $code) use ($app)
{
	switch ($code)
	{
		case 404:
			$message = 'The requested page could not be found.';
			break;
		default:
			$message = "We are sorry, but something went terribly wrong. ($code)";
	}

	if (Env::get(Env::TOE_DEBUG_ON))
	{
		$message = "\n*CODE*\n$code\n*MESSAGE*\n" . $e->getMessage() . "\n*TRACE*\n" . Util::removeFrameworkFromStacktrace($e->getTraceAsString());
	}

	return $app->json(ResponseJson::getJsonResponseArray(false, $message), $code);
});

