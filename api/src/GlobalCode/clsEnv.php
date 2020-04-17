<?php


namespace TOE\GlobalCode;


class clsEnv
{
	const TOE_DEBUG_ON = 'TOE_DEBUG_ON';
	const TOE_DATABASE_HOST = 'TOE_DATABASE_HOST';
	const TOE_DATABASE_PORT = 'TOE_DATABASE_PORT';
	const TOE_DATABASE_USER = 'TOE_DATABASE_USER';
	const TOE_DATABASE_PASSWORD = 'TOE_DATABASE_PASSWORD';
	const TOE_RESET_ACCOUNT_EMAIL = 'TOE_RESET_ACCOUNT_EMAIL';
	const TOE_RESET_CLIENT_ID = 'TOE_RESET_CLIENT_ID';
	const TOE_RESET_CLIENT_SECRET = 'TOE_RESET_CLIENT_SECRET';
	const TOE_RESET_REFRESH_TOKEN = 'TOE_RESET_REFRESH_TOKEN';
	const TOE_AWS_REGION = 'TOE_AWS_REGION';
	const TOE_AWS_ACCESS_KEY = 'TOE_AWS_ACCESS_KEY';
	const TOE_AWS_SECRET_KEY = 'TOE_AWS_SECRET_KEY';
	const TOE_AWS_ASSUME_ROLE_ARN = 'TOE_AWS_ASSUME_ROLE_ARN';
	const TOE_EMAIL_CLIENT = 'TOE_EMAIL_CLIENT';
	const TOE_ENCODED_JWT_KEY = 'TOE_ENCODED_JWT_KEY';
	const TOE_LOG_FILE = 'TOE_LOG_FILE';
	const TOE_LOGGING_LEVEL = 'TOE_LOGGING_LEVEL';
	const TOE_ACCESS_CONTROL_ALLOW_ORIGIN = 'TOE_ACCESS_CONTROL_ALLOW_ORIGIN';
	const TOE_STAGE = 'TOE_STAGE'; //the stage of deployment, dev or prod
	const TOE_DONT_USE_DOTENV = 'TOE_DONT_USE_DOTENV'; //if the .env files should not be looked for

	private static $cache = [];

	const KEY_TYPE_STRING = 'string';
	const KEY_TYPE_INT = 'int';
	const KEY_TYPE_BOOL = 'bool';

	const KEY_TYPES = [
		self::TOE_DEBUG_ON                    => self::KEY_TYPE_BOOL,
		self::TOE_DATABASE_HOST               => self::KEY_TYPE_STRING,
		self::TOE_DATABASE_PORT               => self::KEY_TYPE_INT,
		self::TOE_DATABASE_USER               => self::KEY_TYPE_STRING,
		self::TOE_DATABASE_PASSWORD           => self::KEY_TYPE_STRING,
		self::TOE_RESET_ACCOUNT_EMAIL         => self::KEY_TYPE_STRING,
		self::TOE_RESET_CLIENT_ID             => self::KEY_TYPE_STRING,
		self::TOE_RESET_CLIENT_SECRET         => self::KEY_TYPE_STRING,
		self::TOE_RESET_REFRESH_TOKEN         => self::KEY_TYPE_STRING,
		self::TOE_AWS_REGION                  => self::KEY_TYPE_STRING,
		self::TOE_AWS_ACCESS_KEY              => self::KEY_TYPE_STRING,
		self::TOE_AWS_SECRET_KEY              => self::KEY_TYPE_STRING,
		self::TOE_AWS_ASSUME_ROLE_ARN         => self::KEY_TYPE_STRING,
		self::TOE_EMAIL_CLIENT                => self::KEY_TYPE_STRING,
		self::TOE_ENCODED_JWT_KEY             => self::KEY_TYPE_STRING,
		self::TOE_LOG_FILE                    => self::KEY_TYPE_STRING,
		self::TOE_LOGGING_LEVEL               => self::KEY_TYPE_STRING,
		self::TOE_ACCESS_CONTROL_ALLOW_ORIGIN => self::KEY_TYPE_STRING,
		self::TOE_STAGE                       => self::KEY_TYPE_STRING,
		self::TOE_DONT_USE_DOTENV             => self::KEY_TYPE_STRING
	];

	/**
	 * Retrieves an environment variable based on the keys defined for this application in this class.
	 * The returned type will be converted based on the type defined by the constant KEY_TYPES array in this class
	 *
	 * @param string $key
	 *
	 * @return bool|int|string
	 */
	public static function get($key)
	{
		if(!isset(self::KEY_TYPES[$key]))
		{
			return false;
		}
		if(isset(static::$cache[$key]))
		{
			return static::$cache[$key];
		}
		$val = getenv($key);
		if($val === false)
		{
			static::$cache[$key] = false;

			return false;
		}

		switch(self::KEY_TYPES[$key])
		{
			case self::KEY_TYPE_INT:
				$val = (int)$val;
				break;
			case self::KEY_TYPE_BOOL:
				$val = strtolower($val);
				$val = $val === 'true' || $val === '1' ? true : false;
				break;
			case self::KEY_TYPE_STRING:
			default:
		}

		static::$cache[$key] = $val;

		return $val;
	}
}