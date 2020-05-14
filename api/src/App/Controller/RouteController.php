<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 10/17/2016
 * Time: 5:02 AM
 *
 * The controller that deals with interacting with routes in bulk.
 */

namespace TOE\App\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use TOE\App\Service\Event\EventManager;
use TOE\App\Service\Route\Archive\iObjectStorage;
use TOE\App\Service\Route\Archive\RouteManager;
use TOE\App\Service\Route\Assignment\AssignmentManager;
use TOE\App\Service\Route\Assignment\RouteAssignmentException;
use TOE\App\Service\Route\Assignment\TeamAssignment;
use TOE\App\Service\Team\TeamManager;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\HTTPCodes;
use TOE\GlobalCode\ResponseJson;

class RouteController extends BaseController
{
	/**
	 * @param Request     $request
	 * @param Application $app
	 * @param             $eventId
	 * @param             $orderBy
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 * Response contains the following json data structure:
	 * {
	 *        success: true|false
	 *        routes : [{
	 *                route_name : string
	 *                member_count : int
	 *                teams : [{
	 *                    team_name : string
	 *                    member_count : int
	 *                }]
	 *            }]
	 *        unassignedTeams : [{
	 *                team_name : string
	 *                member_count : int
	 *            }]
	 *
	 * }
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getRouteAssignments(Request $request, Application $app, $eventId, $orderBy)
	{

		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER]);
		/** @var EventManager $eventManager */
		$eventManager = $app['event'];

		if(!$eventManager->eventExists($eventId))
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "Event does not exist in the database."), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		$order = "ASC";
		if($orderBy[0] === '-')
		{
			$orderBy = substr($orderBy, 1);
			$order = "DESC";
		}

		//Create a temp table for easy access to number of people assigned to a route
		/** @var AssignmentManager $assignmentManager */
		$assignmentManager = $app['route.assignment'];
		try
		{
			$assignmentManager->createTeamMembersTemp($eventId);
		}
		catch(RouteAssignmentException $ex)
		{
			$this->logger->err($ex->getMessage(), ['event_id' => $eventId]);

			return $app->json(ResponseJson::getJsonResponseArray(false, 'There was a problem dropping aggregating team membership data.'), HTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		}
		$fullRoutes = 0;
		$emptyRoutes = 0;
		$teamDelim = ",";
		$statDelim = ":";

		//Gets the number of team members on each route
		$routes = $assignmentManager->getRouteAssignmentsOfEvent($eventId, $statDelim, $teamDelim, $orderBy, $order);

		//Get the 'teams' info that each route will need
		$assignedTeams = 0;

		//Assign the teams info to each route
		foreach($routes as &$row)
		{
			if($row['teams'] !== null)
			{
				$teams = explode($teamDelim, $row['teams']);
				$assignedTeams += count($teams);
				foreach($teams as &$team)
				{
					list($teamName, $memberCount) = explode($statDelim, $team);
					$team = ['team_name' => $teamName, 'member_count' => (int)$memberCount];
				}
				$row['teams'] = $teams;
			}
			//Convert the boolean database values to boolean data types in php for sending with JSON
			$row['wheelchair_accessible'] = $row['wheelchair_accessible'] === 'true';
			$row['blind_accessible'] = $row['blind_accessible'] === 'true';
			$row['hearing_accessible'] = $row['hearing_accessible'] === 'true';
			$fullRoutes += ((int)$row['member_count'] === Constants::MAX_ROUTE_MEMBERS) ? 1 : 0;
			$emptyRoutes += ((int)$row['member_count'] === 0) ? 1 : 0;
		}

		//Gets the unassigned teams
		$unassignedTeams = $assignmentManager->getTeamsWithoutRoutes();

		foreach($unassignedTeams as &$team)
		{
			$team['can_drive'] = ($team['can_drive'] === 'true');
			$team['hearing'] = ($team['hearing'] === 'true');
			$team['visual'] = ($team['visual'] === 'true');
			$team['mobility'] = ($team['mobility'] === 'true');
		}

		$stats = [
			'totalRoutes'     => count($routes),
			'fullRoutes'      => $fullRoutes,
			'emptyRoutes'     => $emptyRoutes,
			'teamCount'       => $assignedTeams + count($unassignedTeams),
			'unassignedTeams' => count($unassignedTeams)
		];

		return $app->json(ResponseJson::getJsonResponseArray(true, "", ['routes' => $routes, 'unassignedTeams' => $unassignedTeams, 'stats' => $stats]), HTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	/**
	 * Gets route information for routes assigned to the passed in team for the passed in event.
	 *
	 * @param Request                                   $request
	 * @param Application                               $app
	 * @param                                           $eventId
	 * @param                                           $teamId
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getTeamRouteAssignments(Request $request, Application $app, $eventId, $teamId)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER, Constants::ROLE_PARTICIPANT]);
		if($eventId < 1)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "EventId must be a positive integer."), HTTPCodes::CLI_ERR_NOT_FOUND);
		}

		/** @var TeamManager $teamManager */
		$teamManager = $app['team'];

		//restrict access for users that are only participants
		if(count($this->userInfo->getUserRoles()) === 1 && $this->userInfo->hasRole(Constants::ROLE_PARTICIPANT))
		{
			if(!$teamManager->userIsOnTeam($this->userInfo->getID(), $teamId))
			{
				return $app->json(ResponseJson::getJsonResponseArray(false, "Not authorized to retrieve data about other teams. UserId = " . $this->userInfo->getID()), HTTPCodes::CLI_ERR_NOT_AUTHORIZED);
			}
		}

		/** @var AssignmentManager $assignmentManager */
		$assignmentManager = $app['route.assignment'];
		$routes = $assignmentManager->getTeamRouteInfo($this->userInfo->getID(), $teamId, $eventId);

		/** @var iObjectStorage $routeManager */
		$objectStorage = $app['route.object_storage'];

		foreach($routes as &$route)
		{
			$route['latitude'] = (double)$route['latitude'];
			$route['longitude'] = (double)$route['longitude'];
			$route['zoom'] = (int)$route['zoom'];
			$route['route_file_url'] = $objectStorage->getRouteFileUrl($route['route_file_url']);
		}

		return $app->json(ResponseJson::getJsonResponseArray(true, "", ['routes' => $routes]), HTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	public function getRoutesForEvent(Request $request, Application $app, $eventId)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER]);
		if($eventId < 1)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "Event Id must be a positive number. Passed in '$eventId'"));
		}

		/** @var AssignmentManager $assignmentManager */
		$assignmentManager = $app['route.assignment'];
		$routes = $assignmentManager->getRoutesForEvent($eventId);

		return $app->json(ResponseJson::getJsonResponseArray(true, "", ['routes' => $routes]));
	}

	public function getUnallocatedRoutes(Request $request, Application $app, $eventId)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER]);
		if($eventId < 1)
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "Event Id must be a positive number. Passed in '$eventId'"));
		}

		/** @var AssignmentManager $assignmentManager */
		$assignmentManager = $app['route.assignment'];
		$routes = $assignmentManager->getUnallocatedRoutes($eventId);

		return $app->json(ResponseJson::getJsonResponseArray(true, "", ['routes' => $routes]));
	}

	public function allocateRoute(Request $request, Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER]);

		/** @var RouteManager $routeManager */
		$routeManager = $app['route.manager'];
		$routeId = $app[Constants::PARAMETER_KEY]['routeId'];
		$eventId = $app[Constants::PARAMETER_KEY]['eventId'];
		$zoneId = $app[Constants::PARAMETER_KEY]['zoneId'];

		//verify that the route passed in exists and that it isn't already allocated to the event passed in
		if(!$routeManager->routeExists($zoneId, $routeId))
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "Could not find a route with ID $routeId in zone $zoneId"), HTTPCodes::CLI_ERR_NOT_FOUND);
		}

		/** @var AssignmentManager $assignmentManager */
		$assignmentManager = $app['route.assignment'];
		if($assignmentManager->isRouteAllocatedToEvent($routeId, $eventId))
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, sprintf("Route with id %d is already allocated to event with id %d", $routeId, $eventId)), HTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		if( ($allocationId = $assignmentManager->allocateRouteToEvent($routeId, $eventId)) === false)
		{
			$this->logger->err("Unable to allocate route to event", [
				'route_id' => $routeId,
				'zone_id'  => $zoneId,
				'event_id' => $eventId
			]);

			return $app->json(ResponseJson::getJsonResponseArray(false, "Unable to allocate route to event"), HTTPCodes::SERVER_SERVICE_UNAVAILABLE);
		}

		return $app->json(ResponseJson::getJsonResponseArray(true, "", ['route_allocation_id' => $allocationId]));
	}

	public function deallocateRoute(Request $request, Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER]);

		/** @var AssignmentManager $assignmentManager */
		$assignmentManager = $app['route.assignment'];
		$routeId = $app[Constants::PARAMETER_KEY]['routeId'];
		$eventId = $app[Constants::PARAMETER_KEY]['eventId'];
		if(!$assignmentManager->isRouteAllocatedToEvent($routeId, $eventId))
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "Route {$app[Constants::PARAMETER_KEY]['routeId']} is not currently allocated to {$app[Constants::PARAMETER_KEY]['eventId']}"));
		}
		//check if the route is currently assigned to a team
		if (!empty($teams = $assignmentManager->getRouteTeamsInfo($routeId, $eventId)))
		{
			return $app->json(ResponseJson::getJsonResponseArray(false, "There are teams currently assigned to that route", ['teams' => $teams]));
		}

		if(!$assignmentManager->deallocateRouteFromEvent($routeId, $eventId))
		{
			$this->logger->err("Unable to deallocate route from event", [
				'event_id' => $eventId,
				'route_id' => $routeId
			]);

			return $app->json(ResponseJson::getJsonResponseArray(false, "Unable to deallocate (remove) route from event"), HTTPCodes::SERVER_SERVICE_UNAVAILABLE);
		}

		return $app->json(ResponseJson::getJsonResponseArray(true, ""));
	}

	/**
	 * Runs the route assignment algorithm for the passed in event.
	 * Attempts to assign all available teams to routes for the event such that a full route and empty route is better than two half full routes.
	 *
	 * @param Request $request
	 * @param Application                        $app
	 * @param                                           $eventId
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 * @throws RouteAssignmentException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \TOE\App\Service\Team\TeamException
	 */
	public function assignAllRoutes(Request $request, Application $app, $eventId)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER]);

		/** @var TeamManager $teamManager */
		$teamManager = $app['team'];
		/** @var AssignmentManager $assignmentManager */
		$assignmentManager = $app['route.assignment'];

		$canDrive = [true, false];
		$visionOptions = [true, false];
		$hearingOptions = [true, false];
		$mobilityOptions = [true, false];
		//Get a list of all teams currently without routes.

		$rawTeams = $teamManager->getTeamsWithoutRoutes($eventId);
		$teams = [];
		//Split the list into one hash map, where the key will be the combination of conditions a route must meet to be assigned to that team. The values will be another hash map, where the key is the number of members a team has, the values being team objects.
		foreach($rawTeams as $team)
		{
			$key = $this->getTeamRequirementsKey($team['can_drive'], $team['visual'], $team['hearing'], $team['mobility']);
			if(!isset($teams[$key]))
			{
				$teams[$key] = [];
				for($teamSize = 0; $teamSize <= Constants::MAX_ROUTE_MEMBERS; $teamSize++)
				{
					$teams[$key][] = [];
				}
			}
			$teams[$key][(int)$team['member_count']][] = ['team_id' => $team['team_id'], 'team_name' => $team['team_name'], 'member_count' => (int)$team['member_count'], 'captain_user_id' => $team['captain_user_id']];
		}
		//loop through the combinations of requirements, getting assignments for each route along the way
		/** @var TeamAssignment[] $assignments */
		$assignments = [];
		$totalAssignments = 0;
		foreach($canDrive as $dOption)
		{
			foreach($visionOptions as $vOption)
			{
				foreach($hearingOptions as $hOption)
				{
					foreach($mobilityOptions as $mOption)
					{
						//Get the array key that would be used to find the teams based on their route requirements
						$key = $this->getTeamRequirementsKey($dOption, $vOption, $hOption, $mOption);
						if(!isset($teams[$key]))
						{
							continue;
						}
						$rawRoutes = $assignmentManager->getAccessibleRoutes($eventId, [
							AssignmentManager::ROUTE_TYPE_WALK,
							AssignmentManager::ROUTE_TYPE_BUS
						], $vOption, $hOption, $mOption);

						//first pass through routes, assign routes to the teams that have the exact number of members required
						foreach($rawRoutes as &$route)
						{
							//check if there is a team available with the exact number of members needed for the route
							$remainingSpots = Constants::MAX_ROUTE_MEMBERS - $route['member_count'];
							if(isset($teams[$key][$remainingSpots]) && !empty($teams[$key][$remainingSpots]))
							{
								$curTeam = array_pop($teams[$key][$remainingSpots]);
								$assignments[] = new TeamAssignment($route['route_id'], $curTeam['team_id']);
								$route['member_count'] += $remainingSpots;
							}
						}
						//second pass, try to create combinations of teams that equal the number of users required
						foreach($rawRoutes as &$route)
						{
							for($bigTeamMemberCount = Constants::MAX_ROUTE_MEMBERS - $route['member_count'] - 1; $bigTeamMemberCount > 0; $bigTeamMemberCount--)
							{
								if(count($teams[$key][$bigTeamMemberCount]) > 0)
								{
									//pop a team off the queue with teams of size $bigTeamMemberCount
									$curBigTeam = array_pop($teams[$key][$bigTeamMemberCount]);
									//populate the empty teamCombination array that will hold the potential teams while a full team is attempted to be found.
									$teamCombination = [];
									for($temp = 1; $temp <= $bigTeamMemberCount; $temp++)
									{
										$teamCombination[$temp] = [];
									}
									$teamCombination[$bigTeamMemberCount][] = $curBigTeam;
									$currentTeamSize = $bigTeamMemberCount;
									$counter = Constants::MAX_ROUTE_MEMBERS - $currentTeamSize;
									while($counter > 0 && $currentTeamSize < Constants::MAX_ROUTE_MEMBERS)
									{
										if(count($teams[$key][$counter]) > 0)
										{
											$teamCombination[$counter][] = array_pop($teams[$key][$counter]);
											$currentTeamSize += $counter;
											$counter = Constants::MAX_ROUTE_MEMBERS - $currentTeamSize;
										}
										else
										{
											$counter--;
										}
									}
									//Check to see if a full team could have been made. If not, add the teams back to their queues
									if($currentTeamSize !== Constants::MAX_ROUTE_MEMBERS)
									{
										for($temp = 1; $temp <= $bigTeamMemberCount; $temp++)
										{
											foreach($teamCombination[$temp] as $rejectedTeam)
											{
												array_push($teams[$key][$temp], $rejectedTeam);
											}
										}
									}
									else
									{
										for($temp = 1; $temp <= $bigTeamMemberCount; $temp++)
										{
											while(count($teamCombination[$temp]) > 0)
											{
												$curTeam = array_pop($teamCombination[$temp]);
												$assignments[] = new TeamAssignment($route['route_id'], $curTeam['team_id']);
											}
										}
										$route['member_count'] = Constants::MAX_ROUTE_MEMBERS;
										break;
									}
								}
							}
						}
						//third pass, start assigning teams in a greedy fashion
						foreach($rawRoutes as &$route)
						{
							for($teamSize = Constants::MAX_ROUTE_MEMBERS - $route['member_count'] - 1; $teamSize > 0; $teamSize--)
							{
								while($route['member_count'] + $teamSize <= Constants::MAX_ROUTE_MEMBERS && isset($teams[$key][$teamSize]) && !empty($teams[$key][$teamSize]))
								{
									$curTeam = array_pop($teams[$key][$teamSize]);
									$assignments[] = new TeamAssignment($route['route_id'], $curTeam['team_id']);
									$route['member_count'] += $teamSize;
								}
								if($route['member_count'] >= Constants::MAX_ROUTE_MEMBERS)
								{
									break;
								}
							}
						}
						//Assign the routes to the teams so as to not mess up the route query
						if(!empty($assignments))
						{
							try
							{
								$assignmentManager->assignRoutes($assignments);
							}
							catch(RouteAssignmentException $ex)
							{
								$this->logger->err("Error while assigning all routes", [
									'can_drive'         => $dOption,
									'visually_impaired' => $vOption,
									'hearing_impaired'  => $hOption,
									'mobile_impaired'   => $mOption,
									'err'               => $ex->getMessage()
								]);

								return $app->json(ResponseJson::getJsonResponseArray(false, 'There was a problem updating teams with their assigned routes.'), HTTPCodes::SERVER_SERVICE_UNAVAILABLE);
							}
						}
						//update the total assignment counts
						$totalAssignments += count($assignments);
						$assignments = [];
					}
				}
			}
		}
		//now have assignment pairs, create a statement to update the database with them
		if($totalAssignments === 0)
		{
			return $app->json(ResponseJson::getJsonResponseArray(true, 'No routes found to able to be assigned.'), HTTPCodes::CLI_ERR_NOT_FOUND);
		}

		return $app->json(ResponseJson::getJsonResponseArray(true, "$totalAssignments route assignment pairings were made", ['pairings' => $totalAssignments]), HTTPCodes::SUCCESS_RESOURCE_CREATED);
	}

	/**
	 * Removes all route assignments for the passed in eventId
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Silex\Application                        $app
	 * @param                                           $eventId
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function removeAllRouteAssignments(Request $request, Application $app, $eventId)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([Constants::ROLE_ADMIN, Constants::ROLE_ORGANIZER]);

		/** @var AssignmentManager $assignmentManager */
		$assignmentManager = $app['route.assignment'];

		try
		{
			$assignmentManager->removeAllRouteAssignments($eventId);
			return $app->json(ResponseJson::getJsonResponseArray(true, 'All teams have had their route assignments removed.'), HTTPCodes::SUCCESS_DATA_RETRIEVED);
		}
		catch(RouteAssignmentException $ex)
		{
			$this->logger->err("unable to remove all route assignments from the event", ['event_id' => $eventId]);
			return $app->json(ResponseJson::getJsonResponseArray(false, 'There was an error while removing all route assignments from the event'), HTTPCodes::SERVER_SERVICE_UNAVAILABLE);
		}
	}

	/**
	 * Gets a key for a hash map based on the passed in team requirements.
	 *
	 * @param bool $canDrive Enum - 'true' or 'false'
	 * @param bool $visual   Enum - 'true' or 'false'
	 * @param bool $hearing  Enum - 'true' or 'false'
	 * @param bool $mobility Enum - 'true' or 'false'
	 *
	 * @return string A key used in a hash map for the team.
	 */
	private function getTeamRequirementsKey($canDrive, $visual, $hearing, $mobility)
	{
		return (!$canDrive ? Constants::KEY_CANNOT_DRIVE : Constants::KEY_CAN_DRIVE) .
			(!$visual ? Constants::KEY_NO_VISUAL_IMPAIRMENT : Constants::KEY_VISUAL_IMPAIRMENT) .
			(!$hearing ? Constants::KEY_NO_HEARING_IMPAIRMENT : Constants::KEY_HEARING_IMPAIRMENT) .
			(!$mobility ? Constants::KEY_NO_MOBILITY_IMPAIRMENT : Constants::KEY_MOBILITY_IMPAIRMENT);
	}

	/**
	 * A route being allocated to an event means that it exists in the ROUTE table
	 *
	 * @param $routeId
	 * @param $eventId
	 *
	 * @return bool true if the route is allocated to the event, false otherwise
	 */
	private function routeAllocatedToEvent($routeId, $eventId)
	{

	}
}
