<?php
declare(strict_types=1);

namespace TOE\App\Service\Route\Assignment;


use Pimple\Container;
use Pimple\ServiceProviderInterface;
use TOE\App\Service\NotYetImplementedException;

class AssignmentServiceProvider implements ServiceProviderInterface
{
	public function register(Container $app)
	{
		$app['route.assignment'] = function($app) {
			return new AssignmentManager($app['db']);
		};
	}
}