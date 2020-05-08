<?php
declare(strict_types=1);


namespace TOE\App\Service\Route\Archive;


use Pimple\Container;
use Pimple\ServiceProviderInterface;
use TOE\App\Service\AWSConfig;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\Env;

class RouteServiceProvider implements ServiceProviderInterface
{

	/**
	 * @inheritDoc
	 */
	public function register(Container $app)
	{
		$app['route.object_storage'] = function() {
			switch(Env::get(Env::TOE_OBJECT_STORAGE_TYPE))
			{
				case 's3':
					$s3 = new \Aws\S3\S3Client(AWSConfig::getStandardConfig());
					return new S3ObjectStore($s3, Env::get(Env::TOE_ROUTE_BUCKET));
					break;
				case 'file':
				default:
					return new FileObjectStore(Constants::ROUTE_HOSTING_DIRECTORY);
			}
		};
		$app['route.manager'] = function($app) {
			return new RouteManager($app['db']);
		};
	}
}