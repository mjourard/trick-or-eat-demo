<?php


namespace TOE\GlobalCode;


class clsEnv
{
	const TOE_DEBUG_ON = 'TOE_DEBUG_ON';
	const TOE_DATABASE_HOST = 'TOE_DATABASE_HOST';
	const TOE_DATABASE_PORT = 'TOE_DATABASE_PORT';
	const TOE_DATABASE_USER = 'TOE_DATABASE_USER';
	const TOE_DATABASE_PASSWORD = 'TOE_DATABASE_PASSWORD';
	const TOE_REDIS_PASSWORD = 'TOE_REDIS_PASSWORD';
	const TOE_REDIS_LOGGING_IP = 'TOE_REDIS_LOGGING_IP';
	const TOE_REDIS_LOGGING_PORT = 'TOE_REDIS_LOGGING_PORT';
	const TOE_RESET_ACCOUNT_EMAIL = 'TOE_RESET_ACCOUNT_EMAIL';
	const TOE_RESET_CLIENT_ID = 'TOE_RESET_CLIENT_ID';
	const TOE_RESET_CLIENT_SECRET = 'TOE_RESET_CLIENT_SECRET';
	const TOE_RESET_REFRESH_TOKEN = 'TOE_RESET_REFRESH_TOKEN';
	const TOE_ENCODED_JWT_KEY = 'TOE_ENCODED_JWT_KEY';
	const TOE_LOG_FILE = 'TOE_LOG_FILE';
	const TOE_LOGGING_LEVEL = 'TOE_LOGGING_LEVEL';
	const TOE_ACCESS_CONTROL_ALLOW_ORIGIN = 'TOE_ACCESS_CONTROL_ALLOW_ORIGIN';
	const TOE_STAGE = 'TOE_STAGE'; //the stage of deployment, dev or prod

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
		self::TOE_REDIS_PASSWORD              => self::KEY_TYPE_STRING,
		self::TOE_REDIS_LOGGING_IP            => self::KEY_TYPE_STRING,
		self::TOE_REDIS_LOGGING_PORT          => self::KEY_TYPE_INT,
		self::TOE_RESET_ACCOUNT_EMAIL         => self::KEY_TYPE_STRING,
		self::TOE_RESET_CLIENT_ID             => self::KEY_TYPE_STRING,
		self::TOE_RESET_CLIENT_SECRET         => self::KEY_TYPE_STRING,
		self::TOE_RESET_REFRESH_TOKEN         => self::KEY_TYPE_STRING,
		self::TOE_ENCODED_JWT_KEY             => self::KEY_TYPE_STRING,
		self::TOE_LOG_FILE                    => self::KEY_TYPE_STRING,
		self::TOE_LOGGING_LEVEL               => self::KEY_TYPE_STRING,
		self::TOE_ACCESS_CONTROL_ALLOW_ORIGIN => self::KEY_TYPE_STRING,
		self::TOE_STAGE                       => self::KEY_TYPE_STRING
	];

	/**
	 * Retrieves an environment variable based on the keys defined for this application in this class.
	 * The returned type will be converted based on the type defined by the constant KEY_TYPES array in this class
	 *
	 * @param string $key
	 *
	 * @return bool|int|string
	 */
	public static function Get($key)
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