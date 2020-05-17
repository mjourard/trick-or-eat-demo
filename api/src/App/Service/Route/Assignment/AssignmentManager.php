<?php
declare(strict_types=1);

namespace TOE\App\Service\Route\Assignment;


use Doctrine\DBAL\Connection;
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
			CREATE TEMPORARY TABLE " . self::TEMP_TABLE_NAME . " 
				COLLATE = 'utf8_unicode_ci'
    			CHARACTER SET = 'utf8'
				ENGINE = 'InnoDB'
			AS(
				SELECT
					t.team_id,
					t.event_id,
					ra.route_id,
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
				LEFT JOIN team_route tr
					ON t.team_id = tr.team_id
				LEFT JOIN route_allocation ra
					ON tr.route_allocation_id = ra.route_allocation_id
					AND ra.event_id = :event_id
				WHERE t.event_id = :event_id
				GROUP BY t.team_id, ra.route_id
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
			->leftJoin('ra', 'route_allocation', 'ral', 'ra.route_id = ral.route_id')
			->leftJoin('ra', self::TEMP_TABLE_NAME, 'tm', 'ra.route_id = tm.route_id')
			->where('ral.event_id = :event_id')
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
			->from(self::TEMP_TABLE_NAME)
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
			'ral.start_time as route_start_time',
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
			->leftJoin('t', 'team_route', 'tr', 't.team_id = tr.team_id')
			->leftJoin('tr', 'route_allocation', 'ral', 'tr.route_allocation_id = ral.route_allocation_id')
			->leftJoin('ral', 'route_archive', 'ra', 'ral.route_id = ra.route_id')
			->leftJoin('ral', 'bus', 'b', 'ral.bus_id = b.bus_id')
			->leftJoin('ra', 'zone', 'z', 'ra.zone_id = z.zone_id')
			->where('m.user_id = :user_id')
			->andWhere('t.team_id = :team_id')
			->andWhere('ral.event_id = :event_id')
			->setParameter(':user_id', $userId)
			->setParameter(':team_id', $teamId)
			->setParameter(':event_id', $eventId);

		return $qb->execute()->fetchAll();
	}

	/**
	 * Gets info about the team that is currently assigned to a route
	 *
	 * @param int $routeId
	 * @param int $eventId
	 *
	 * @return mixed[] The teams that are currently assigned to that route
	 */
	public function getRouteTeamsInfo(int $routeId, int $eventId)
	{
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select([
			't.team_id',
			't.name',
			't.captain_user_id'
		])
			->from('team', 't')
			->leftJoin('t', 'team_route', 'tr', 't.team_id = tr.team_id')
			->leftJoin('tr', 'route_allocation', 'ral', 'tr.route_allocation_id = ral.route_allocation_id')
			->where($qb->expr()->eq('ral.route_id', $routeId))
			->andWhere($qb->expr()->eq('t.event_id', $eventId));
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
			'ral.route_id',
			'z.zone_name',
			'ra.route_name',
			'ra.wheelchair_accessible',
			'ra.blind_accessible',
			'ra.hearing_accessible'
		)
			->from('route_allocation', 'ral')
			->leftJoin('ral', 'route_archive', 'ra', 'ral.route_id = ra.route_id')
			->leftJoin('ra', 'zone', 'z', 'ra.zone_id = z.zone_id')
			->where('ral.event_id = :eventId')
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
			->leftJoin('ra', 'route_allocation', 'ral', 'ral.route_id = ra.route_id')
			->leftJoin('ra', 'zone', 'z', 'ra.zone_id = z.zone_id')
			->where('ral.event_id is NULL')
			->orWhere('NOT ral.event_id = :eventId')
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
			->from('route_allocation')
			->where('route_id = :routeId')
			->andWhere('event_id = :eventId')
			->setParameter(':routeId', $routeId, Constants::SILEX_PARAM_INT)
			->setParameter(':eventId', $eventId, Constants::SILEX_PARAM_INT);
		return !empty($qb->execute()->fetchAll());
	}

	/**
	 * Allocates a route to an event (creates a record in the route table from a record in the route_archive table)
	 *
	 * @param int       $routeId
	 * @param int       $eventId
	 *
	 * @return int|false the new route_allocation_id if the route was successfully alloctated, false otherwise
	 */
	public function allocateRouteToEvent(int $routeId, int $eventId)
	{
		//TODO: have this function take in a StartTime for the route
		//Issues: will need to translate the user's timezone into UTC. What happens if another user's timezone is different? Probably should display a user's timezone whenever we are displaying times
		$qb = $this->dbConn->createQueryBuilder();
		$qb->insert('route_allocation')
			->values([
				'route_id'   => ':routeId',
				'event_id'   => ':eventId',
				'start_time' => 'NOW()',
			])
			->setParameter(':routeId', $routeId, Constants::SILEX_PARAM_INT)
			->setParameter(':eventId', $eventId, Constants::SILEX_PARAM_INT);

		if ($qb->execute() === 0)
		{
			return false;
		}
		$allocationId = $this->dbConn->lastInsertId();
		if (!empty($allocationId))
		{
			return (int)$allocationId;
		}
		return false;
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
		$qb->delete('route_allocation')
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
			'ral.route_allocation_id',
			'count(m.user_id) AS member_count'
		)
			->from('route_allocation', 'ral')
			->leftJoin('ral', 'route_archive', 'ra', 'ral.route_id = ra.route_id')
			->leftJoin('ral', 'team_route', 'tr', 'ral.route_allocation_id = tr.route_allocation_id')
			->leftJoin('tr', 'team', 't', 'tr.team_id = t.team_id')
			->leftJoin('t', 'member', 'm', 't.team_id = m.team_id')
			->where('ral.event_id = :event_id')
			->andWhere($qb->expr()->in('ra.type', [':route_types']))
			->andWhere($blindParam)
			->andWhere($hearingParam)
			->andWhere($mobileParam)
			->groupBy('ral.route_allocation_id')
			->having("member_count < " . Constants::MAX_ROUTE_MEMBERS)
			->setParameter(':event_id', $eventId)
			->setParameter(':route_types', $routeTypes, Connection::PARAM_STR_ARRAY);
		$routes = $qb->execute()->fetchAll();

		foreach($routes as &$route)
		{
			$route['member_count'] = (int)$route['member_count'];
		}
		return $routes;
	}

	/**
	 * Takes in the team route assignments and assigns them
	 *
	 * @param TeamAssignment[] $teamAssignments
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws RouteAssignmentException
	 */
	public function assignRoutes(array $teamAssignments)
	{
		$values = [];
		foreach($teamAssignments as $assignment)
		{
			$values[] = implode(",", [$assignment->teamId, $assignment->routeAllocationId]);
		}
		$valuesStr = implode("),(", $values);
		$q = <<<TEAMROUTEINSERT
INSERT INTO team_route (
	team_id,
	route_allocation_id
)
VALUES
($valuesStr)
ON DUPLICATE KEY UPDATE 
	route_allocation_id = VALUES(route_allocation_id)
TEAMROUTEINSERT;

		$query = $this->dbConn->prepare($q);
		if (!$query->execute())
		{
			throw new RouteAssignmentException(print_r($this->dbConn->errorInfo(), true));
		}
	}

	/**
	 * Assigns the route allocation to the team as long as that would not put the number of people assigned to the route over the maximum
	 *
	 * @param int $teamId
	 * @param int $routeAllocationId
	 *
	 * @return bool
	 * @throws RouteAssignmentException If assigning the new team to the route allocation would put it over the maximum
	 */
	public function assignRouteToTeam(int $teamId, int $routeAllocationId)
	{
		//check to see if there are too many teams assigned to the route already
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select([
			'COUNT(*) as cnt'
		])
			->from('member', 'm')
			->leftJoin('m', 'team', 't', 'm.team_id = t.team_id')
			->leftJoin('t', 'team_route', 'tr', 't.team_id = tr.team_id')
			->leftJoin('tr', 'route_allocation', 'ral', 'tr.route_allocation_id = ral.route_allocation_id')
			->where($qb->expr()->eq('ral.route_allocation_id', ':route_allocation_id'))
			->setParameter(':route_allocation_id', $routeAllocationId);

		$row = $qb->execute()->fetch();
		if (empty($row) || $row['cnt'] === null)
		{
			throw new RouteAssignmentException("Could not find the number of members currently assigned to route allocation");
		}
		$count = (int)$row['cnt'];

		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('ra.required_people')
			->from('route_archive', 'ra')
			->leftJoin('ra', 'route_allocation', 'ral', 'ra.route_id = ral.route_id')
			->where($qb->expr()->eq('ral.route_allocation_id', ':route_allocation_id'))
			->setParameter(':route_allocation_id', $routeAllocationId);
		$row = $qb->execute()->fetch();
		if (empty($row) || $row['required_people'] === null)
		{
			throw new RouteAssignmentException("Unable to get the required people assigned to route allocation");
		}
		$requiredPeople = (int)$row['required_people'];


		if ($count >= $requiredPeople)
		{
			throw new RouteAssignmentException("Assigning the passed in team would cause the number of team members assigned to the route allocation to surpass the max required people for the route");
		}

		$qb = $this->dbConn->createQueryBuilder();
		$qb->insert('team_route')
			->values([
				'team_id' => ':team_id',
				'route_allocation_id' => ':route_allocation_id'
			])
			->setParameter(':team_id', $teamId)
			->setParameter(':route_allocation_id', $routeAllocationId);
		return $qb->execute() === 1;
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

		$qb->delete('team_route', 'tr')
			->leftJoin('tr', 'route_allocation', 'ral', 'tr.route_allocation_id = ral.route_allocation_id')
			->where('ral.event_id = :event_id')
			->setParameter(':event_id', $eventId);
		if ($qb->execute() === 0)
		{
			throw new RouteAssignmentException("No route assignments set for the passed in event");
		}
	}

	/**
	 * Assigns the passed in bus id to the route allocation
	 *
	 * @param int $routeAllocationId
	 * @param int $busId
	 *
	 * @return bool true if the assignment was a success, false otherwise
	 * @throws RouteAssignmentException Throws an exception if the bus and route allocations are for different zones
	 */
	public function assignBusToRouteAllocation(int $routeAllocationId, int $busId)
	{
		//ensure the route allocation is for the right zone
		$qb = $this->dbConn->createQueryBuilder();
		$qb->select('ral.route_allocation_id', 'b.zone_id')
			->from('route_allocation', 'ral')
			->leftJoin('ral', 'route_archive', 'ra', 'ral.route_id = ra.route_id')
			->innerJoin('ra', 'bus', 'b', 'ra.zone_id = b.zone_id')
			->where($qb->expr()->eq('ral.route_allocation_id', ':route_allocation_id'))
			->andWhere($qb->expr()->eq('b.bus_id', ':bus_id'))
			->setParameter(':route_allocation_id', $routeAllocationId)
			->setParameter(':bus_id', $busId);

		$row = $qb->execute()->fetch();
		if (empty($row['route_allocation_id']) || empty($row['zone_id']))
		{
			throw new RouteAssignmentException("Unable to assign bus to route allocation: bus and route allocation are not for the same zone");
		}

		$qb = $this->dbConn->createQueryBuilder();
		$qb->update('route_allocation')
			->set('bus_id', ':bus_id')
			->where($qb->expr()->eq('route_allocation_id', ':route_allocation_id'))
			->setParameter(':bus_id', $busId)
			->setParameter(':route_allocation_id', $routeAllocationId);
		return $qb->execute() === 1;
	}

	/**
	 * Gets the available route types
	 *
	 * @return string[]
	 */
	public function getRouteTypes()
	{
		return [
			self::ROUTE_TYPE_WALK,
			self::ROUTE_TYPE_BUS,
			self::ROUTE_TYPE_DRIVE
		];
	}
}