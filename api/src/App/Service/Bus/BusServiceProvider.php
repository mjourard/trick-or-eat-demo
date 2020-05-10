<?php
declare(strict_types=1);

namespace TOE\App\Service\Bus;


use Pimple\Container;
use Pimple\ServiceProviderInterface;

class BusServiceProvider implements ServiceProviderInterface
{

	public function register(Container $app)
	{
		$app['bus'] = function($app) {
				return new BusManager($app['db']);
		};
	}
}