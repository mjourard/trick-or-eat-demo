<?php
declare(strict_types=1);

namespace TOE\App\Service\Team;


use Pimple\Container;
use Pimple\ServiceProviderInterface;

class TeamServiceProvider implements ServiceProviderInterface
{

	public function register(Container $app)
	{
		$app['team'] = function($app) {
			return new TeamManager($app['db']);
		};
	}
}