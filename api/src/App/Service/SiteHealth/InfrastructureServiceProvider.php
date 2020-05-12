<?php
declare(strict_types=1);

namespace TOE\App\Service\SiteHealth;


use Pimple\Container;
use Pimple\ServiceProviderInterface;

class InfrastructureServiceProvider implements ServiceProviderInterface
{

	public function register(Container $app)
	{
		$app['infrastructure'] = function($app)
		{
			return new InfrastructureManager($app['db']);
		};
	}
}