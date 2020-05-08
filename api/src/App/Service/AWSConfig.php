<?php
declare(strict_types=1);


namespace TOE\App\Service;


use TOE\GlobalCode\Env;

class AWSConfig
{
	/**
	 * Gets the standard config for an AWS Client object used throughout this system
	 *
	 * @param array $config
	 *
	 * @return array The config that can be used
	 */
	public static function getStandardConfig(array $config = [])
	{
		if(empty($config['region']))
		{
			$config['region'] = Env::get(Env::TOE_AWS_REGION);
		}
		if(empty($config['key']))
		{
			if(!empty($key = Env::get(Env::TOE_AWS_ACCESS_KEY)))
			{
				$config['key'] = $key;
			}
		}
		if(empty($config['secret']))
		{
			if(!empty($secret = Env::get(Env::TOE_AWS_SECRET_KEY)))
			{
				$config['secret'] = $secret;
			}
		}
		if(empty($config['roleArn']))
		{
			if(!empty($roleArn = Env::get(Env::TOE_AWS_ASSUME_ROLE_ARN)))
			{
				$config['roleArn'] = $roleArn;
			}
		}

		return $config;
	}
}