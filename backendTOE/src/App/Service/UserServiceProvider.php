<?php
namespace TOE\App\Service;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

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
