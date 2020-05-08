<?php
declare(strict_types=1);

namespace TOE\App\Service\User;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class UserServiceProvider implements ServiceProviderInterface
{
	public function register(Container $app)
	{
		$app['user'] = $app->factory(function($app) {
			/* @var $info UserInfoStorage */
			$info = $app['user_info'];

			return new UserProvider($info->getToken());
		});

		$app['user_info'] = function() {
			return new UserInfoStorage();
		};

		$app['user.lookup'] = function($app) {
			return new UserLookupService($app['db']);
		};
	}
}
