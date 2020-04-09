<?php


namespace TOE\App\Service\Email;


use TOE\GlobalCode\clsEnv;

class ClientFactory
{
	const CLIENT_TYPE_GMAIL = 'gmail';
	const CLIENT_TYPE_AWS_SES = 'ses';

	public static function getClient($type = null, $config = [])
	{
		$type = self::getValidClientType($type);
		switch($type)
		{
			case self::CLIENT_TYPE_AWS_SES:
				return self::getAWSClient($config);
			case self::CLIENT_TYPE_GMAIL:
				return self::getGmailClient($config);
			default:
				throw new EmailException("Unable to determine the type of email client to initialize: '$type'");
		}
	}

	/**
	 * Checks the passed in type of email client to return. If it is not valid, then the environment variables will be checked for valid values
	 *
	 * Otherwise, it will default to the gmail client
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	private static function getValidClientType($type)
	{
		$type = self::checkClientType($type);

		//$type is null or valid, detect environment variables to search for a valid client
		if($type !== null)
		{
			return $type;
		}
		$type = self::checkClientType(clsEnv::get(clsEnv::TOE_EMAIL_CLIENT));
		if($type !== null)
		{
			return $type;
		}
		//determine if within AWS

		if($type !== null)
		{
			return $type;
		}

		return self::CLIENT_TYPE_GMAIL;
	}

	/**
	 * @param string $type
	 *
	 * @return null
	 */
	private static function checkClientType($type)
	{
		switch($type)
		{
			case self::CLIENT_TYPE_AWS_SES:
			case self::CLIENT_TYPE_GMAIL:
				return $type;
		}

		return null;
	}

	/**
	 * Gets an AWS SES client
	 *
	 * @param array $config
	 *
	 * @return AwsSesClient
	 */
	private static function getAWSClient($config)
	{

		if(empty($config['region']))
		{
			$config['region'] = clsEnv::get(clsEnv::TOE_AWS_REGION);
		}
		if(empty($config['key']))
		{
			if(!empty($key = clsEnv::get(clsEnv::TOE_AWS_ACCESS_KEY)))
			{
				$config['key'] = $key;
			}
		}
		if(empty($config['secret']))
		{
			if(!empty($secret = clsEnv::get(clsEnv::TOE_AWS_SECRET_KEY)))
			{
				$config['secret'] = $secret;
			}
		}
		if(empty($config['roleArn']))
		{
			if(!empty($roleArn = clsEnv::get(clsEnv::TOE_AWS_ASSUME_ROLE_ARN)))
			{
				$config['roleArn'] = $roleArn;
			}
		}

		return new AwsSesClient($config);
	}

	/**
	 * Gets a gmail client
	 *
	 * @param array $config
	 *
	 * @return GmailClient
	 */
	private static function getGmailClient($config)
	{
		$keyEnvMap = [
			'smtp_debug'          => clsEnv::TOE_DEBUG_ON,
			'oauth_user_email'    => clsEnv::TOE_RESET_ACCOUNT_EMAIL,
			'oauth_client_id'     => clsEnv::TOE_RESET_CLIENT_ID,
			'oauth_client_secret' => clsEnv::TOE_RESET_CLIENT_SECRET,
			'oauth_refresh_token' => clsEnv::TOE_RESET_REFRESH_TOKEN,
		];
		foreach($keyEnvMap as $configKey => $envKey)
		{
			if (empty($config[$configKey]))
			{
				if (!empty($value = clsEnv::get($envKey)))
				{
					$config[$configKey] = $value;
				}
			}
		}
		return new GmailClient($config);
	}
}