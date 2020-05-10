<?php
declare(strict_types=1);

namespace TOE\App\Service\Route\Assignment;


class TeamAssignment
{
	public $routeAllocationId;
	public $teamId;

	public function __construct($routeAllocationId, $teamId)
	{
		$this->routeAllocationId = $routeAllocationId;
		$this->teamId = $teamId;
	}
}