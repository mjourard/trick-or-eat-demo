<?php
declare(strict_types=1);

namespace TOE\App\Service\Route\Assignment;


class TeamAssignment
{
	public $routeId;
	public $teamId;

	public function __construct($routeId, $teamId)
	{
		$this->routeId = $routeId;
		$this->teamId = $teamId;
	}
}