<?php


namespace TOE\App;


class ServiceContainer
{
	/**
	 * @var DAL
	 */
	public $db;

	public $logger;

	public function __construct(DAL $db)
	{
		$this->db = $db;
	}
}