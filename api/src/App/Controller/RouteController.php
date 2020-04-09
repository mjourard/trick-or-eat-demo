<?php
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
use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsHTTPCodes;
use TOE\GlobalCode\clsResponseJson;

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
	 */
	public function getRouteAssignments(Request $request, Application $app, $eventId, $orderBy)
	{

		$this->initializeInstance($app);
		$this->unauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER]);

		if (!$this->eventExists($eventId))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Event does not exist in the database."), clsHTTPCodes::CLI_ERR_BAD_REQUEST);
		}

		$order = "ASC";
		if ($orderBy[0] === '-')
		{
			$orderBy = substr($orderBy, 1);
			$order = "DESC";
		}

		//Create a temp table for easy access to number of people assigned to a route
		$q = "DROP TEMPORARY TABLE IF EXISTS team_members";
		$query = $this->db->prepare($q);
		if (!$query->execute())
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, 'There was a problem dropping the team_members temp table.'), clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		};
		$fullRoutes = 0;
		$emptyRoutes = 0;
		$q = "
			CREATE TEMPORARY TABLE team_members 
				COLLATE = 'utf8_unicode_ci'
    			CHARACTER SET = 'utf8'
				ENGINE = 'InnoDB'
			AS(
				SELECT
					t.team_id,
					t.event_id,
					t.route_id,
					t.captain_user_id,
					t.name,
					CASE MAX(m.can_drive = 'true')
						WHEN 1 then 'true'
						ELSE 'false' END
						AS can_drive,
					CASE MAX(u.hearing = 'true')
						WHEN 1 then 'true'
						ELSE 'false' END
						AS hearing,
					CASE MAX(u.visual = 'true')
						WHEN 1 then 'true'
						ELSE 'false' END
						AS visual,
					CASE MAX(u.mobility = 'true') 
						WHEN 1 then 'true'
						ELSE 'false' END
						AS mobility,
					COUNT(m.team_id) as member_count
				FROM team t
				LEFT JOIN member m
					ON t.team_id = m.team_id
				LEFT JOIN user u
					ON m.user_id = u.user_id
				WHERE t.event_id = :event_id
				GROUP BY t.team_id
			);";

		$query = $this->db->prepare($q);
		$query->bindValue(':event_id', $eventId);
		if (!$query->execute())
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, 'There was a problem creating the team_members temp table.'), clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		};

		$teamDelim = ",";
		$statDelim = ":";

		//Gets the number of team members on each route
		$qb = $this->db->createQueryBuilder();
		$qb->select(
			'ra.route_name',
			'ra.route_id',
			'ra.type',
			'ra.wheelchair_accessible',
			'ra.blind_accessible',
			'ra.hearing_accessible',
			'COALESCE(SUM(tm.member_count), 0) as member_count',
			"GROUP_CONCAT(tm.name,'$statDelim', tm.member_count SEPARATOR '$teamDelim') as teams"
		)
			->from('route_archive', 'ra')
			->leftJoin('ra', 'route', 'r', 'ra.route_id = r.route_id')
			->leftJoin('ra', 'team_members', 'tm', 'ra.route_id = tm.route_id')
			->where('r.event_id = :event_id')
			->groupBy('ra.route_id')
			->orderBy($orderBy, $order)
			->setParameter(':event_id', $eventId);

//		echo $qb->getSQL();
		$routes = $qb->execute()->fetchAll();

		//Get the 'teams' info that each route will need
		$assignedTeams = 0;

		//Assign the teams info to each route
		foreach ($routes as &$row)
		{
			if ($row['teams'] !== null)
			{
				$teams = explode($teamDelim, $row['teams']);
				//TODO: add check for when theere are no teams assigned
				$assignedTeams += count($teams);
				foreach ($teams as &$team)
				{
					$temp = explode($statDelim, $team);
					$team = ['team_name' => $temp[0], 'member_count' => (int)$temp[1]];
				}

				$row['teams'] = $teams;
			}
			//Convert the boolean database values to boolean data types in php for sending with JSON
			$row['wheelchair_accessible'] = ($row['wheelchair_accessible'] === 'true');
			$row['blind_accessible'] = ($row['blind_accessible'] === 'true');
			$row['hearing_accessible'] = ($row['hearing_accessible'] === 'true');
			$fullRoutes += ((int)$row['member_count'] === clsConstants::MAX_ROUTE_MEMBERS) ? 1 : 0;
			$emptyRoutes += ((int)$row['member_count'] === 0) ? 1 : 0;
		}
		//Gets the unassigned teams
		$qb = $this->db->createQueryBuilder();
		$qb->select(
			'name as team_name',
			'can_drive',
			'hearing',
			'visual',
			'mobility',
			'member_count'
		)
			->from('team_members')
			->where('route_id is NULL')
			->andWhere('member_count > 0');

		$unassignedTeams = $qb->execute()->fetchAll();

		foreach ($unassignedTeams as &$team)
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

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ['routes' => $routes, 'unassignedTeams' => $unassignedTeams, 'stats' => $stats]), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	/**
	 * Gets route information for routes assigned to the passed in team for the passed in event.
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Silex\Application                        $app
	 * @param                                           $eventId
	 * @param                                           $teamId
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getRouteAssignmentsForTeam(Request $request, Application $app, $eventId, $teamId)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER, clsConstants::ROLE_PARTICIPANT]);
		if ($eventId < 1)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "EventId must be a positive integer."), clsHTTPCodes::CLI_ERR_NOT_FOUND);
		}

		//restrict access for users that are only participants
		if (count($this->userInfo->getUserRoles()) === 1 && $this->userInfo->hasRole(clsConstants::ROLE_PARTICIPANT))
		{
			$qb = $this->db->createQueryBuilder();
			$qb->select('team_id')
				->from('member')
				->where('user_id = :user_id')
				->setParameter(":user_id", $this->userInfo->getID());

			$results = $qb->execute()->fetch();
			if (empty($results) || $results['team_id'] != $teamId)
			{
				return $app->json(clsResponseJson::GetJsonResponseArray(false, "Not authorized to retrieve data about other teams. UserId = " . $this->userInfo->getID()), clsHTTPCodes::CLI_ERR_NOT_AUTHORIZED);
			}
		}

		$qb = $this->db->createQueryBuilder();
		$qb->select(
			'ra.route_name',
			'ra.route_file_url',
			'r.start_time as route_start_time',
			'ra.type',
			'b.bus_name',
			'b.start_time as bus_start_time',
			'b.end_time',
			'z.latitude',
			'z.longitude',
			'z.zoom',
			'z.zone_name'
		)
			->from('member', 'm')
			->leftJoin('m', 'team', 't', 'm.team_id = t.team_id')
			->leftJoin('t', 'route', 'r', 't.route_id = r.route_id')
			->leftJoin('r', 'route_archive', 'ra', 'r.route_id = ra.route_id')
			->leftJoin('r', 'bus', 'b', 'r.bus_id = b.bus_id')
			->leftJoin('ra', 'zone', 'z', 'ra.zone_id = z.zone_id')
			->where('m.user_id = :user_id')
			->andWhere('t.team_id = :team_id')
			->andWhere('r.event_id = :event_id')
			->setParameter(':user_id', $this->userInfo->getID())
			->setParameter(':team_id', $teamId)
			->setParameter(':event_id', $eventId);

		$routes = $qb->execute()->fetchAll();

		foreach ($routes as &$route)
		{
			$route['latitude'] = (double)$route['latitude'];
			$route['longitude'] = (double)$route['longitude'];
			$route['zoom'] = (int)$route['zoom'];
		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ['routes' => $routes]), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	public function getRoutesForEvent(Request $request, Application $app, $eventId)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER]);
		if ($eventId < 1)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Event Id must be a positive number. Passed in '$eventId'"));
		}

		$qb = $this->db->createQueryBuilder();
		$qb->select(
			'r.route_id',
			'z.zone_name',
			'ra.route_name',
			'ra.wheelchair_accessible',
			'ra.blind_accessible',
			'ra.hearing_accessible'
		)
			->from('route', 'r')
			->leftJoin('r', 'route_archive', 'ra', 'r.route_id = ra.route_id')
			->leftJoin('ra', 'zone', 'z', 'ra.zone_id = z.zone_id')
			->where('r.event_id = :eventId')
			->setParameter(':eventId', $eventId, clsConstants::SILEX_PARAM_INT);

		$routes = $qb->execute()->fetchAll();

		foreach ($routes as &$route)
		{
			$route['route_id'] = (int)$route['route_id'];
			$route['wheelchair_accessible'] = $route['wheelchair_accessible'] === "true" ? true : false;
			$route['blind_accessible'] = $route['blind_accessible'] === "true" ? true : false;
			$route['hearing_accessible'] = $route['hearing_accessible'] === "true" ? true : false;

		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ['routes' => $routes]));

	}

	public function getUnallocatedRoutes(Request $request, Application $app, $eventId)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER]);
		if ($eventId < 1)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Event Id must be a positive number. Passed in '$eventId'"));
		}

		$qb = $this->db->createQueryBuilder();
		$qb->select(
			'ra.route_id',
			'z.zone_id',
			'z.zone_name',
			'ra.route_name',
			'ra.wheelchair_accessible',
			'ra.blind_accessible',
			'ra.hearing_accessible'
		)
			->from('route_archive', 'ra')
			->leftJoin('ra', 'route', 'r', 'r.route_id = ra.route_id')
			->leftJoin('ra', 'zone', 'z', 'ra.zone_id = z.zone_id')
			->where('r.event_id is NULL')
			->orWhere('NOT r.event_id = :eventId')
			->setParameter(':eventId', $eventId, clsConstants::SILEX_PARAM_INT);

		$routes = $qb->execute()->fetchAll();

		foreach ($routes as &$route)
		{
			$route['route_id'] = (int)$route['route_id'];
			$route['zone_id'] = (int)$route['zone_id'];
			$route['wheelchair_accessible'] = $route['wheelchair_accessible'] === "true" ? true : false;
			$route['blind_accessible'] = $route['blind_accessible'] === "true" ? true : false;
			$route['hearing_accessible'] = $route['hearing_accessible'] === "true" ? true : false;

		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ['routes' => $routes]));
	}

	public function allocateRoute(Request $request, Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER]);
		//verify that the route passed in exists and that it isn't already allocated to the event passed in

		if (!$this->routeExists($app[clsConstants::PARAMETER_KEY]['zoneId'], $app[clsConstants::PARAMETER_KEY]['routeId']))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Could not find a route with ID {$app[clsConstants::PARAMETER_KEY]['routeId']} in zone {$app[clsConstants::PARAMETER_KEY]['zoneId']}"));
		}

		if ($this->routeAllocatedToEvent($app[clsConstants::PARAMETER_KEY]['routeId'], $app[clsConstants::PARAMETER_KEY]['eventId']))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Route {$app[clsConstants::PARAMETER_KEY]['routeId']} is already allocated to event {$app[clsConstants::PARAMETER_KEY]['eventId']}"));
		}

		$qb = $this->db->createQueryBuilder();
		$qb->insert('route')
			->values([
				'route_id'   => ':routeId',
				'event_id'   => ':eventId',
				'start_time' => 'NOW()',
			])
			->setParameter(':routeId', $app[clsConstants::PARAMETER_KEY]['routeId'], clsConstants::SILEX_PARAM_INT)
			->setParameter(':eventId', $app[clsConstants::PARAMETER_KEY]['eventId'], clsConstants::SILEX_PARAM_INT);

		$qb->execute();

		return $app->json(clsResponseJson::GetJsonResponseArray(true, ""));
	}

	public function deallocateRoute(Request $request, Application $app)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER]);
		if (!$this->routeAllocatedToEvent($app[clsConstants::PARAMETER_KEY]['routeId'], $app[clsConstants::PARAMETER_KEY]['eventId']))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "Route {$app[clsConstants::PARAMETER_KEY]['routeId']} is not currently allocated to {$app[clsConstants::PARAMETER_KEY]['eventId']}"));
		}

		$qb = $this->db->createQueryBuilder();
		$qb->delete('route')
			->where('route_id = :routeId')
			->andWhere('event_id = :eventId')
			->setParameter(':routeId', $app[clsConstants::PARAMETER_KEY]['routeId'], clsConstants::SILEX_PARAM_INT)
			->setParameter(':eventId', $app[clsConstants::PARAMETER_KEY]['eventId'], clsConstants::SILEX_PARAM_INT);

		$qb->execute();

		return $app->json(clsResponseJson::GetJsonResponseArray(true, ""));

	}

	/**
	 * Runs the route assignment algorithm for the passed in event.
	 * Attempts to assign all available teams to routes for the event such that a full route and empty route is better than two half full routes.
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Silex\Application                        $app
	 * @param                                           $eventId
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function assignAllRoutes(Request $request, Application $app, $eventId)
	{
		$this->initializeInstance($app);
		$this->unauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER]);

		$canDrive = ['true', 'false'];
		$visionOptions = ['true', 'false'];
		$hearingOptions = ['true', 'false'];
		$mobilityOptions = ['true', 'false'];
		//Get a list of all teams currently without routes.
		$q = "
		SELECT 
			m.team_id,
			t.name AS team_name,
			t.captain_user_id,
			CASE MAX(m.can_drive = 'true')
				WHEN 1 then 'true'
                ELSE 'false' END
                AS can_drive,
			CASE MAX(u.hearing = 'true')
				WHEN 1 then 'true'
                ELSE 'false' END
                AS hearing,
			CASE MAX(u.visual = 'true')
				WHEN 1 then 'true'
                ELSE 'false' END
                AS visual,
			CASE MAX(u.mobility = 'true') 
				WHEN 1 then 'true'
                ELSE 'false' END
                AS mobility,
			count(m.team_id) AS member_count
		FROM member m
		LEFT JOIN user u
			ON m.user_id = u.user_id
		LEFT JOIN team t
			ON m.team_id = t.team_id
		WHERE m.event_id = :event_id
		AND t.route_id is NULL
		GROUP BY m.team_id
		HAVING member_count > 0
		ORDER BY t.name";

		$query = $this->db->prepare($q);
		$query->bindValue(':event_id', $eventId);
		if (!$query->execute())
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, 'There was a problem retrieving teams with active members.'), clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
		};
		$rawTeams = $query->fetchAll();
		$teams = [];
		//Split the list into one hash map, where the key will be the combination of conditions a route must meet to be assigned to that team. The values will be another hash map, where the key is the number of members a team has, the values being team objects.
		foreach ($rawTeams as $team)
		{
			$key = $this->getTeamRequirementsKey($team['can_drive'], $team['visual'], $team['hearing'], $team['mobility']);
			if (!isset($teams[$key]))
			{
				$teams[$key] = [];
				for ($teamSize = 0; $teamSize <= clsConstants::MAX_ROUTE_MEMBERS; $teamSize++)
				{
					$teams[$key][] = [];
				}
			}
			$teams[$key][(int)$team['member_count']][] = ['team_id' => $team['team_id'], 'team_name' => $team['team_name'], 'member_count' => (int)$team['member_count'], 'captain_user_id' => $team['captain_user_id']];
		}
		//loop through the combinations of requirements, getting assignments for each route along the way
		$assignments = [];
		$totalAssignments = 0;
		foreach ($canDrive as $dOption)
		{
			foreach ($visionOptions as $vOption)
			{
				foreach ($hearingOptions as $hOption)
				{
					foreach ($mobilityOptions as $mOption)
					{
						//Get the array key that would be used to find the teams based on their route requirements
						$key = $this->getTeamRequirementsKey($dOption, $vOption, $hOption, $mOption);
						if (!isset($teams[$key]))
						{
							continue;
						}
						$driveParam = $dOption === 'true' ? "" : "NOT ra.type = 'Drive'";
						$blindParam = $vOption === 'true' ? "ra.blind_accessible = 'true'" : "";
						$hearingParam = $hOption === 'true' ? "ra.hearing_accessible = 'true'" : "";
						$mobileParam = $mOption === 'true' ? "ra.wheelchair_accessible = 'true'" : "";
						//Grab all routes that aren't full yet and meet the current requirements.
						$qb = $this->db->createQueryBuilder();
						$qb->select(
							'r.route_id',
							'count(m.user_id) AS member_count'
						)
							->from('route', 'r')
							->leftJoin('r', 'route_archive', 'ra', 'r.route_id = ra.route_id')
							->leftJoin('r', 'team', 't', 'r.route_id = t.route_id')
							->leftJoin('t', 'member', 'm', 't.team_id = m.team_id')
							->where('r.event_id = :event_id')
							->andWhere($driveParam)
							->andWhere($blindParam)
							->andWhere($hearingParam)
							->andWhere($mobileParam)
							->groupBy('r.route_id')
							->having("member_count < " . clsConstants::MAX_ROUTE_MEMBERS)
							->setParameter(':event_id', $eventId);

						$rawRoutes = $qb->execute()->fetchAll();

						//first pass through routes, assign routes to the teams that have the exact number of members required
						foreach ($rawRoutes as &$route)
						{
							//check if there is a team available with the exact number of members needed for the route
							$route['member_count'] = (int)$route['member_count'];
							$remainingSpots = clsConstants::MAX_ROUTE_MEMBERS - $route['member_count'];
							if (isset($teams[$key][$remainingSpots]) && !empty($teams[$key][$remainingSpots]))
							{
								$curTeam = array_pop($teams[$key][$remainingSpots]);
								$assignments[] = ['route_id' => $route['route_id'], 'team_id' => $curTeam['team_id'], 'captain_user_id' => $curTeam['captain_user_id'], 'team_name' => $curTeam['team_name']];
								$route['member_count'] += $remainingSpots;
							}
						}
						//second pass, try to create combinations of teams that equal the number of users required
						foreach ($rawRoutes as &$route)
						{
							for ($bigTeamMemberCount = clsConstants::MAX_ROUTE_MEMBERS - $route['member_count'] - 1; $bigTeamMemberCount > 0; $bigTeamMemberCount--)
							{
								if (count($teams[$key][$bigTeamMemberCount]) > 0)
								{
									//pop a team off the queue with teams of size $bigTeamMemberCount
									$curBigTeam = array_pop($teams[$key][$bigTeamMemberCount]);
									//populate the empty teamCombination array that will hold the potential teams while a full team is attempted to be found.
									$teamCombination = [false];
									for ($temp = 1; $temp <= $bigTeamMemberCount; $temp++)
									{
										$teamCombination[$temp] = [];
									}
									$teamCombination[$bigTeamMemberCount][] = $curBigTeam;
									$currentTeamSize = $bigTeamMemberCount;
									$counter = clsConstants::MAX_ROUTE_MEMBERS - $currentTeamSize;
									while ($counter > 0 && $currentTeamSize < clsConstants::MAX_ROUTE_MEMBERS)
									{
										if (count($teams[$key][$counter]) > 0)
										{
											$teamCombination[$counter][] = array_pop($teams[$key][$counter]);
											$currentTeamSize += $counter;
											$counter = clsConstants::MAX_ROUTE_MEMBERS - $currentTeamSize;
										}
										else
										{
											$counter--;
										}
									}
									//Check to see if a full team could have been made. If not, add the teams back to their queues
									if ($currentTeamSize !== clsConstants::MAX_ROUTE_MEMBERS)
									{
										for ($temp = 1; $temp <= $bigTeamMemberCount; $temp++)
										{
											foreach ($teamCombination[$temp] as $rejectedTeam)
											{
												array_push($teams[$key][$temp], $rejectedTeam);
											}
										}
									}
									else
									{
										for ($temp = 1; $temp <= $bigTeamMemberCount; $temp++)
										{
											while (count($teamCombination[$temp]) > 0)
											{
												$curTeam = array_pop($teamCombination[$temp]);
												$assignments[] = ['route_id' => $route['route_id'], 'team_id' => $curTeam['team_id'], 'captain_user_id' => $curTeam['captain_user_id'], 'team_name' => $curTeam['team_name']];
											}
										}
										$route['member_count'] = clsConstants::MAX_ROUTE_MEMBERS;
										break;
									}
								}
							}
						}
						//third pass, start assigning teams in a greedy fashion
						foreach ($rawRoutes as &$route)
						{
							for ($teamSize = clsConstants::MAX_ROUTE_MEMBERS - $route['member_count'] - 1; $teamSize > 0; $teamSize--)
							{
								while ($route['member_count'] + $teamSize <= clsConstants::MAX_ROUTE_MEMBERS && isset($teams[$key][$teamSize]) && !empty($teams[$key][$teamSize]))
								{
									$curTeam = array_pop($teams[$key][$teamSize]);
									$assignments[] = ['route_id' => $route['route_id'], 'team_id' => $curTeam['team_id'], 'captain_user_id' => $curTeam['captain_user_id'], 'team_name' => $curTeam['team_name']];
									$route['member_count'] += $teamSize;
								}
								if ($route['member_count'] >= clsConstants::MAX_ROUTE_MEMBERS)
								{
									break;
								}
							}
						}
						//Assign the routes to the teams so as to not mess up the route query
						if (!empty($assignments))
						{
							//create the value pairs;
							$values = "";
							$parameters = [];
							$index = 0;
							foreach ($assignments as $assignment)
							{
								$parameters["name$index"] = $assignment['team_name'];
								$parameters["event_id$index"] = $eventId;
								$values .= "({$assignment['team_id']},{$assignment['route_id']},:event_id$index,{$assignment['captain_user_id']},:name$index),";
								$index++;
							}
							$values = rtrim($values, ",");
							$q = "
								INSERT INTO team 
								(
									team_id,
									route_id,
									event_id,
									captain_user_id,
									name
								) 
								VALUES
									$values
								ON DUPLICATE KEY UPDATE 
									team_id=VALUES(team_id),
									route_id=VALUES(route_id),
									event_id=VALUES(event_id),
									captain_user_id=VALUES(captain_user_id),
									name=VALUES(name)";
							//TODO: change this to executeUpdate
							$query = $this->db->prepare($q);

							foreach ($parameters as $key => $value)
							{
								$type = stripos($key, "event") === false ? \PDO::PARAM_STR : \PDO::PARAM_INT;
								$query->bindValue(":$key", $value, $type);
							}

							if (!$query->execute())
							{
								return $app->json(clsResponseJson::GetJsonResponseArray(false, 'There was a problem updating teams with their assigned routes.'), clsHTTPCodes::SERVER_ERROR_GENERIC_DATABASE_FAILURE);
							};
						}
						//update the total assignment counts
						$totalAssignments += count($assignments);
						$assignments = [];
					}
				}
			}
		}
		//now have assignment pairs, create a statement to update the database with them
		if ($totalAssignments === 0)
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(true, 'No routes found to able to be assigned.'), clsHTTPCodes::CLI_ERR_NOT_FOUND);
		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "$totalAssignments route assignment pairings were made"), clsHTTPCodes::SUCCESS_RESOURCE_CREATED);
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
		$this->unauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER]);

		$qb = $this->db->createQueryBuilder();

		$qb->update('team')
			->set('route_id', 'null')
			->where('event_id = :event_id')
			->setParameter(':event_id', $eventId);

		$qb->execute();

		return $app->json(clsResponseJson::GetJsonResponseArray(true, 'All teams have had their route assignments removed.'), clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
	}

	/**
	 * Gets a key for a hash map based on the passed in team requirements.
	 *
	 * @param string $canDrive Enum - 'true' or 'false'
	 * @param string $visual   Enum - 'true' or 'false'
	 * @param string $hearing  Enum - 'true' or 'false'
	 * @param string $mobility Enum - 'true' or 'false'
	 *
	 * @return string A key used in a hash map for the team.
	 */
	private function getTeamRequirementsKey($canDrive, $visual, $hearing, $mobility)
	{
		return ($canDrive === 'false' ? clsConstants::KEY_CANNOT_DRIVE : clsConstants::KEY_CAN_DRIVE) .
			($visual === 'false' ? clsConstants::KEY_NO_VISUAL_IMPAIRMENT : clsConstants::KEY_VISUAL_IMPAIRMENT) .
			($hearing === 'false' ? clsConstants::KEY_NO_HEARING_IMPAIRMENT : clsConstants::KEY_HEARING_IMPAIRMENT) .
			($mobility === 'false' ? clsConstants::KEY_NO_MOBILITY_IMPAIRMENT : clsConstants::KEY_MOBILITY_IMPAIRMENT);
	}

	/**
	 * Checks if an event with the passed in eventId exists.
	 *
	 * @param $eventId
	 *
	 * @return bool
	 */
	private function eventExists($eventId)
	{
		if (!is_int($eventId))
		{
			return false;
		}

		$qb = $this->db->createQueryBuilder();

		$qb->select('event_id')
			->from('event')
			->where('event_id = :event_id')
			->setParameter('event_id', $eventId, clsConstants::SILEX_PARAM_INT);

		return !empty($qb->execute()->fetchAll());

	}

	private function routeExists($zoneId, $routeId)
	{
		$qb = $this->db->createQueryBuilder();
		$qb->select(
			'route_id'
		)
			->from('route_archive')
			->where('route_id = :routeId')
			->andWhere('zone_id = :zoneId')
			->setParameter(':routeId', $routeId, clsConstants::SILEX_PARAM_INT)
			->setParameter(':zoneId', $zoneId, clsConstants::SILEX_PARAM_INT);

		return !empty($qb->execute()->fetchAll());

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
		$qb = $this->db->createQueryBuilder();
		$qb->select('route_id')
			->from('route')
			->where('route_id = :routeId')
			->andWhere('event_id = :eventId')
			->setParameter(':routeId', $routeId, clsConstants::SILEX_PARAM_INT)
			->setParameter(':eventId', $eventId, clsConstants::SILEX_PARAM_INT);

		return !empty($qb->execute()->fetchAll());
	}
}

?>
