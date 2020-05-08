<?php
declare(strict_types=1);

namespace TOE\App\Service\Password;


use Pimple\Container;
use Pimple\ServiceProviderInterface;
use TOE\App\Service\NotYetImplementedException;

class PasswordRequestServiceProvider implements ServiceProviderInterface
{

	public function register(Container $app)
	{
		$app['password.request'] = function($app) {
			return new PasswordRequestManager($app['db']);
		};
	}
}