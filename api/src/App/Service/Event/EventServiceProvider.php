<?php
declare(strict_types=1);

namespace TOE\App\Service\Event;


use Pimple\Container;
use Pimple\ServiceProviderInterface;

class EventServiceProvider implements ServiceProviderInterface
{

	public function register(Container $app)
	{
		$app['event'] = function($app) {
				return new EventManager($app['db']);
		};

		$app['event.registration'] = function($app) {
				return new RegistrationManager($app['db']);
		};
	}
}