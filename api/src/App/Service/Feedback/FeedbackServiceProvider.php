<?php
declare(strict_types=1);

namespace TOE\App\Service\Feedback;


use Pimple\Container;
use Pimple\ServiceProviderInterface;

class FeedbackServiceProvider implements ServiceProviderInterface
{
	public function register(Container $app)
	{
		$app['feedback'] = function($app) {
			return new FeedbackManager($app['db']);
		};
	}
}