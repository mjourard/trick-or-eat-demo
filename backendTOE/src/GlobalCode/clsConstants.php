<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 10/22/2016
 * Time: 6:42 AM
 */

namespace TOE\GlobalCode;

class clsConstants
{
	const STANDARD_ID_REGEX             = '\d*[1-9]\d*';
	const STANDARD_NOT_WHITESPACE_REGEX = '[^\s].+';

	const SUPER_ADMIN_ID             = "1";
	const ROUTE_REQUIREMENTS         = 4;
	const KEY_CAN_DRIVE              = "C";
	const KEY_CANNOT_DRIVE           = "Cn";
	const KEY_VISUAL_IMPAIRMENT      = "V";
	const KEY_NO_VISUAL_IMPAIRMENT   = "Vn";
	const KEY_HEARING_IMPAIRMENT     = "H";
	const KEY_NO_HEARING_IMPAIRMENT  = "Hn";
	const KEY_MOBILITY_IMPAIRMENT    = "M";
	const KEY_NO_MOBILITY_IMPAIRMENT = "Mn";
	const MAX_ROUTE_MEMBERS          = 6;
	const JOIN_CODE_REGEX            = '/^\d{3}$/';

	const USER_PLACEHOLDER_FIRST_NAME = "TOEGeneratedFirstName";
	const USER_PLACEHOLDER_LAST_NAME  = "TOEGeneratedFirstName";
	const USER_PLACEHOLDER_REGION_ID  = 9;

	const PLACEHOLDER_EMAIL = 'toeholder.com';

	const SILEX_PARAM_STRING   = "string";
	const SILEX_PARAM_BOOL     = "boolean";
	const SILEX_PARAM_INT      = "integer";
	const SILEX_PARAM_DOUBLE   = "double";
	const SILEX_PARAM_DECIMAL  = "decimal";
	const SILEX_PARAM_DATETIME = "datetime";

	const DATABASE_NAME = "scotchbox";
	const DATABASE_HOST = "127.0.0.1";
	const DATABASE_PORT = 3306;

	const MINIMUM_PASSWORD_LENGTH = 8;

	//Turns on debug mode. Need a better way to distinguish between production and development mode. Until then, this should never be merged to master as true
	const DEBUG_ON = true;

	const PARAMETER_KEY = 'params';

	/**
	 * if ROLE_ALL is given to route: all incoming requests have access.
	 * if ROLE_ALL is given to user: has access to all routes
	 */
	const ROLE_ALL = "*";

	const ROLE_ADMIN       = 'admin';
	const ROLE_ORGANIZER   = 'organizer';
	const ROLE_MODERATOR   = 'moderator';
	const ROLE_EDITOR      = 'editor';
	const ROLE_PARTICIPANT = 'participant';
	const ROLE_DRIVER      = 'driver';

	const ROUTE_HOSTING_DIRECTORY = __DIR__ . "/../../public/route-files";
	const ROUTE_HOSTING_URL       = "backendtoe/public/route-files";

	const REDIS_LOGGING_IP   = '131.104.49.37';
	const REDIS_LOGGING_PORT = 6379;
	const REDIS_ERROR_KEY    = "errors";

	const EMAIL_RESET_LINK = "#/reset-password/";
}