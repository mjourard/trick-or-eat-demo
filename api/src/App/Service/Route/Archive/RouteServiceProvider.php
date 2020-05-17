<?php
declare(strict_types=1);


namespace TOE\App\Service\Route\Archive;


use Aws\S3\S3Client;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use TOE\App\Service\AWS\S3Helper;
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
		//make it a factory for easier functional testing
		$app['route.object_storage'] = $app->factory(function() {
			switch(Env::get(Env::TOE_OBJECT_STORAGE_TYPE))
			{
				case 's3':
					$wrapper = new S3Helper(AWSConfig::getStandardConfig());
					/** @var S3Client $s3 */
					$s3 = $wrapper->getClient();
					return new S3ObjectStore($s3, Env::get(Env::TOE_S3_ROUTE_BUCKET));
					break;
				case 'file':
				default:
					return new FileObjectStore(
						Constants::ROUTE_HOSTING_DIRECTORY,
								self::getLocalRoutefileUrlPrefix()
					);
			}
		});
		$app['route.manager'] = function($app) {
			return new RouteManager($app['db']);
		};
	}

	/**
	 * @return string
	 */
	public static function getLocalRoutefileUrlPrefix()
	{
		$proto = "http";
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
		{
			$proto = "https";
		}
		$domain = "localhost";
		if (!empty($_SERVER['HTTP_HOST']))
		{
			$domain = $_SERVER['HTTP_HOST'];
		}
		//possibly include the port in the future, but not necessary now
		return sprintf("%s://%s/route-files", $proto, $domain);
	}
}