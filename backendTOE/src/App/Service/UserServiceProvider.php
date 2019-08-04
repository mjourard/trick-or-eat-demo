<?php
namespace Silex\Provider;
use TOE\App\Service\UserProvider;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use TOE\App\Service\UserInfoStorage;

class UserServiceProvider implements ServiceProviderInterface
{
	public function register(Container $app)
	{
		$app['user'] = $app->factory(function ($app)
		{
			/* @var $info UserInfoStorage */
			$info = $app['user_info'];
			return new UserProvider($info->GetToken());
		});

		$app['user_info'] = function($app)
		{
			return new UserInfoStorage();
		};
	}
}

?>
