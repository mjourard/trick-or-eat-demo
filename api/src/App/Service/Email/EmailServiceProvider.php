<?php
declare(strict_types=1);

namespace TOE\App\Service\Email;


use Pimple\Container;
use Pimple\ServiceProviderInterface;
use TOE\App\Service\AWSConfig;
use TOE\GlobalCode\Env;

class EmailServiceProvider implements ServiceProviderInterface
{
	public function register(Container $app)
	{
		$app['email'] = function() {
			return ClientFactory::getClient(
				Env::get(Env::TOE_EMAIL_CLIENT),
				AWSConfig::getStandardConfig()
			);
		};
	}
}