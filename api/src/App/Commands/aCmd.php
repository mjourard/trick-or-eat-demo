<?php


namespace TOE\App\Commands;


use Symfony\Component\Console\Command\Command;
use TOE\App\ServiceContainer;

class aCmd extends Command
{

	protected $container;

	public function __construct(ServiceContainer $container)
	{
		$this->container = $container;
		parent::__construct();
	}
}