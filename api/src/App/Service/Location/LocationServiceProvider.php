<?php
declare(strict_types=1);

namespace TOE\App\Service\Location;


use Pimple\Container;
use Pimple\ServiceProviderInterface;

class LocationServiceProvider implements ServiceProviderInterface
{

	public function register(Container $app)
	{
		$app['region'] = function($app) {
			return new RegionManager($app['db']);
		};

		$app['zone'] = function($app) {
			return new ZoneManager($app['db']);
		};
	}
}