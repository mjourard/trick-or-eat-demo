<?php
declare(strict_types=1);


namespace TOE\App\Service\Email;


use TOE\GlobalCode\Env;

class ClientFactory
{
	public const CLIENT_TYPE_GMAIL = 'gmail';
	public const CLIENT_TYPE_AWS_SES = 'ses';

	public static function getClient($type = null, $config = [])
	{
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
	 * Gets an AWS SES client
	 *
	 * @param array $config
	 *
	 * @return AwsSesClient
	 */
	private static function getAWSClient($config)
	{
		return new AwsSesClient($config);
	}

	/**
	 * Gets a gmail client
	 *
	 * @param array $config
	 *
	 * @return GmailClient
	 * @throws EmailException
	 */
	private static function getGmailClient($config)
	{
		$keyEnvMap = [
			'smtp_debug'          => Env::TOE_DEBUG_ON,
			'oauth_user_email'    => Env::TOE_RESET_ACCOUNT_EMAIL,
			'oauth_client_id'     => Env::TOE_RESET_CLIENT_ID,
			'oauth_client_secret' => Env::TOE_RESET_CLIENT_SECRET,
			'oauth_refresh_token' => Env::TOE_RESET_REFRESH_TOKEN,
		];
		foreach($keyEnvMap as $configKey => $envKey)
		{
			if (empty($config[$configKey]))
			{
				if (!empty($value = Env::get($envKey)))
				{
					$config[$configKey] = $value;
				}
			}
		}
		return new GmailClient($config);
	}
}