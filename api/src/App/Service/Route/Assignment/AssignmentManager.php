<?php
declare(strict_types=1);

namespace TOE\App\Service\Route\Assignment;


use TOE\App\Service\BaseDBService;
use TOE\GlobalCode\Constants;

class AssignmentManager extends BaseDBService
{
	public const TEMP_TABLE_NAME = 'team_members';
	/**
	 * routes that must be driven to in order to get there
	 */
	public const ROUTE_TYPE_DRIVE = 'Drive';
	/**
	 * routes that must be walked to in order to get there
	 */
	public const ROUTE_TYPE_WALK = 'Walk';
	/**
	 * routes in which one of the trick-or-eat buses arrive at and pickup from
	 */
	public const ROUTE_TYPE_BUS = 'Bus';

	/**
	 * Creates a temporary table of team members on the routes of an event
	 *
	 * @param int $eventId
	 *
	 * @throws RouteAssignmentException
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function createTeamMembersTemp(int $eventId)
	{
		//Create a temp table for easy access to number of people assigned to a route
		$q = "DROP TEMPORARY TABLE IF EXISTS " . self::TEMP_TABLE_NAME;
		$query = $this->dbConn->prepare($q);
		if(!$query->execute())
		{
			throw new RouteAssignmentException("Dropping temp table failed: " . print_r($query->errorInfo(), true));
		}
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
		$query = $this->dbConn->prepare($q);
		$query->bindValue('event_id', $eventId);
		if(!$query->execute())
		{
			throw new RouteAssignmentException("Create temp table failed: " . print_r($query->errorInfo(), true));
		}
	}

	/**
	 * Gets the routes that are active for an event ready to be returned for the routeAssignments call
	 *
	 * @param int    $eventId
	 * @param string $statDelim
	 * @param string $teamDelim
	 * @param string $orderByCol
	 * @param string $order
	 *
	 * @return mixed[]
	 */
	public function getRouteAssignmentsOfEvent(int $eventId, string $statDelim, string $teamDelim, string $orderByCol, $order = 'ASC')
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			'ra.route_name',
			'ra.route_id',
			'ra.type',
			'ra.wheelchair_accessible',
			'ra.blind_accessible',
			'ra.hearing_accessible',
			'COALESCE(SUM(tm.member_count), 0) as member_count',
			"GROUP_CONCAT(tm.name,:stat_delim, tm.member_count SEPARATOR :team_delim) as teams"
		)
			->from('route_archive', 'ra')
			->leftJoin('ra', 'route', 'r', 'ra.route_id = r.route_id')
			->leftJoin('ra', 'team_members', 'tm', 'ra.route_id = tm.route_id')
			->where('r.event_id = :event_id')
			->groupBy('ra.route_id')
			->orderBy($orderByCol, $order)
			->setParameter(':event_id', $eventId)
			->setParameter(':stat_delim', $statDelim)
			->setParameter(':team_delim', $teamDelim);
		return $qb->execute()->fetchAll();
	}

	/**
	 * Gets the teams from the temp table team_members that don't currently have a route assigned to them
	 *
	 * @return array
	 */
	public function getTeamsWithoutRoutes()
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			'name as team_name',
			'can_drive',
			'hearing',
			'visual',
			'mobility',
			'member_count'
		)
			->from('team_members')
			->where($qb->expr()->isNull('route_id'))
			->andWhere($qb->expr()->gt('member_count', 0));

		return $qb->execute()->fetchAll();
	}

	/**
	 * Gets the assigned route info of the passed in user and team
	 *
	 * @param int $userId
	 * @param int $teamId
	 * @param int $eventId
	 *
	 * @return mixed[]
	 */
	public function getTeamRouteInfo(int $userId, int $teamId, int $eventId)
	{
		$qb = $this->dbConn->createQueryBuilder();
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
			->setParameter(':user_id', $userId)
			->setParameter(':team_id', $teamId)
			->setParameter(':event_id', $eventId);

		return $qb->execute()->fetchAll();
	}

	/**
	 * Gets all routes for an event
	 *
	 * @param int $eventId
	 *
	 * @return array
	 */
	public function getRoutesForEvent(int $eventId)
	{
		$qb = $this->dbConn->createQueryBuilder();
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
			->setParameter(':eventId', $eventId, Constants::SILEX_PARAM_INT);
		$routes = $qb->execute()->fetchAll();

		foreach($routes as &$route)
		{
			$route['route_id'] = (int)$route['route_id'];
			$route['wheelchair_accessible'] = $route['wheelchair_accessible'] === "true";
			$route['blind_accessible'] = $route['blind_accessible'] === "true";
			$route['hearing_accessible'] = $route['hearing_accessible'] === "true";
		}

		return $routes;
	}

	/**
	 * Gets the routes that are active for an event but are not yet assigned to a team
	 *
	 * @param int $eventId
	 *
	 * @return mixed[]
	 */
	public function getUnallocatedRoutes(int $eventId)
	{
		$qb = $this->dbConn->createQueryBuilder();
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
			->setParameter(':eventId', $eventId, Constants::SILEX_PARAM_INT);

		$routes = $qb->execute()->fetchAll();
		foreach($routes as &$route)
		{
			$route['route_id'] = (int)$route['route_id'];
			$route['zone_id'] = (int)$route['zone_id'];
			$route['wheelchair_accessible'] = $route['wheelchair_accessible'] === "true";
			$route['blind_accessible'] = $route['blind_accessible'] === "true";
			$route['hearing_accessible'] = $route['hearing_accessible'] === "true";
		}

		return $routes;
	}

	/**
	 * Checks if the passed in route is allocated to the passed in event (route is active)
	 *
	 * @param int $routeId
	 * @param int $eventId
	 *
	 * @return bool
	 */
	public function isRouteAllocatedToEvent(int $routeId, int $eventId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('route_id')
			->from('route')
			->where('route_id = :routeId')
			->andWhere('event_id = :eventId')
			->setParameter(':routeId', $routeId, Constants::SILEX_PARAM_INT)
			->setParameter(':eventId', $eventId, Constants::SILEX_PARAM_INT);
		return !empty($qb->execute()->fetchAll());
	}

	/**
	 * Allocates a route to an event (creates a record in the route table from a record in the route_archive table)
	 *
	 * @param int $routeId
	 * @param int $eventId
	 *
	 * @return bool true if the route was successfully alloctated, false otherwise
	 */
	public function allocateRouteToEvent(int $routeId, int $eventId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->insert('route')
			->values([
				'route_id'   => ':routeId',
				'event_id'   => ':eventId',
				'start_time' => 'NOW()',
			])
			->setParameter(':routeId', $routeId, Constants::SILEX_PARAM_INT)
			->setParameter(':eventId', $eventId, Constants::SILEX_PARAM_INT);

		return $qb->execute() > 0;
	}

	/**
	 * deallocates (removes) a route from an event
	 *
	 * @param int $routeId
	 * @param int $eventId
	 *
	 * @return bool true if the route was successfully removed, false otherwise
	 */
	public function deallocateRouteFromEvent(int $routeId, int $eventId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->delete('route')
			->where('route_id = :routeId')
			->andWhere('event_id = :eventId')
			->setParameter(':routeId', $routeId, Constants::SILEX_PARAM_INT)
			->setParameter(':eventId', $eventId, Constants::SILEX_PARAM_INT);
		return $qb->execute() > 0;
	}

	/**
	 * Gets routes of the passed in route types that match the accessibility requirements
	 *
	 * @param int   $eventId
	 * @param array $routeTypes
	 * @param bool  $blindAccessible
	 * @param bool  $hearingAccessible
	 * @param bool  $mobilityAccessible
	 *
	 * @return array
	 * @throws RouteAssignmentException
	 */
	public function getAccessibleRoutes(int $eventId, array $routeTypes, bool $blindAccessible, bool $hearingAccessible, bool $mobilityAccessible)
	{
		foreach($routeTypes as $type)
		{
			switch($type)
			{
				case self::ROUTE_TYPE_BUS:
				case self::ROUTE_TYPE_DRIVE:
				case self::ROUTE_TYPE_WALK:
					break;
				default:
					throw new RouteAssignmentException("Unknown route type passed in: '$type'");
			}
		}
		$blindParam = $blindAccessible ? "ra.blind_accessible = 'true'" : "";
		$hearingParam = $hearingAccessible ? "ra.hearing_accessible = 'true'" : "";
		$mobileParam = $mobilityAccessible ? "ra.wheelchair_accessible = 'true'" : "";
		//Grab all routes that aren't full yet and meet the current requirements.
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select(
			'r.route_id',
			'count(m.user_id) AS member_count'
		)
			->from('route', 'r')
			->leftJoin('r', 'route_archive', 'ra', 'r.route_id = ra.route_id')
			->leftJoin('r', 'team', 't', 'r.route_id = t.route_id')
			->leftJoin('t', 'member', 'm', 't.team_id = m.team_id')
			->where('r.event_id = :event_id')
			->andWhere('ra.type in (:route_types)')
			->andWhere($blindParam)
			->andWhere($hearingParam)
			->andWhere($mobileParam)
			->groupBy('r.route_id')
			->having("member_count < " . Constants::MAX_ROUTE_MEMBERS)
			->setParameter(':route_types', $routeTypes)
			->setParameter(':event_id', $eventId);
		$routes = $qb->execute()->fetchAll();
		foreach($routes as &$route)
		{
			$route['member_count'] = (int)$route['member_count'];
		}
		return $routes;
	}

	/**
	 * Takes in the team route assignments and assigns them as long as the routes and teams belong to the passed in event_id
	 *
	 * @param int   $eventId
	 * @param array $teamAssignments
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws RouteAssignmentException
	 */
	public function assignRoutes(int $eventId, array $teamAssignments)
	{
		$values = "";
		$teamIds = [];
		foreach($teamAssignments as $assignment)
		{
			$values .= sprintf("WHEN team_id = %d THEN %d\n", $assignment->teamId, $assignment->routeId);
			$teamIds[] = $assignment->teamId;
		}
		$teamIdsStr = implode(",", $teamIds);
		$q = <<<TEAMUPDATE
UPDATE TEAM SET route_id = CASE
$values
ELSE route_id
END
WHERE team_id in ($teamIdsStr)
AND event_id = :event_id
TEAMUPDATE;

		$query = $this->dbConn->prepare($q);
		$query->bindValue("event_id", $eventId);
		if (!$query->execute())
		{
			throw new RouteAssignmentException(print_r($this->dbConn->errorInfo(), true));
		}
	}

	/**
	 * Removes all route assignments for the passed in event
	 *
	 * @param int $eventId
	 *
	 * @throws RouteAssignmentException
	 */
	public function removeAllRouteAssignments(int $eventId)
	{
		$qb = $this->dbConn->createQueryBuilder();

		$qb->update('team')
			->set('route_id', 'null')
			->where('event_id = :event_id')
			->setParameter(':event_id', $eventId);
		if ($qb->execute() === 0)
		{
			throw new RouteAssignmentException("No route assignments set for the passed in event");
		}
	}
}