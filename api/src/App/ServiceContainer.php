<?php


namespace TOE\App;


use TOE\App\Service\AWS\AuroraDataAPIWrapper;

class ServiceContainer
{
	/**
	 * @var DAL
	 */
	public $db;

	public $logger;
	/**
	 * @var AuroraDataAPIWrapper
	 */
	public $aurora;

	public function __construct(?DAL $db, ?AuroraDataAPIWrapper $aurora)
	{
		$this->db = $db;
		$this->aurora = $aurora;
	}
}