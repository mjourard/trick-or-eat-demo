<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 10/22/2016
 * Time: 6:42 AM
 */

namespace TOE\GlobalCode;

class Constants
{
	public const STANDARD_ID_REGEX             = '\d*[1-9]\d*';
	public const STANDARD_NOT_WHITESPACE_REGEX = '[^\s].+';

	public const SUPER_ADMIN_ID             = "1";
	public const ROUTE_REQUIREMENTS         = 4;
	public const KEY_CAN_DRIVE              = "C";
	public const KEY_CANNOT_DRIVE           = "Cn";
	public const KEY_VISUAL_IMPAIRMENT      = "V";
	public const KEY_NO_VISUAL_IMPAIRMENT   = "Vn";
	public const KEY_HEARING_IMPAIRMENT     = "H";
	public const KEY_NO_HEARING_IMPAIRMENT  = "Hn";
	public const KEY_MOBILITY_IMPAIRMENT    = "M";
	public const KEY_NO_MOBILITY_IMPAIRMENT = "Mn";
	public const MAX_ROUTE_MEMBERS          = 6;
	public const JOIN_CODE_REGEX            = '/^\d{3}$/';

	public const USER_PLACEHOLDER_FIRST_NAME = "TOEGeneratedFirstName";
	public const USER_PLACEHOLDER_LAST_NAME  = "TOEGeneratedLastName";
	public const USER_PLACEHOLDER_REGION_ID  = 9;

	public const PLACEHOLDER_EMAIL = 'toeholder.com';

	public const SILEX_PARAM_STRING   = "string";
	public const SILEX_PARAM_BOOL     = "boolean";
	public const SILEX_PARAM_INT      = "integer";
	public const SILEX_PARAM_DOUBLE   = "double";
	public const SILEX_PARAM_DECIMAL  = "decimal";
	public const SILEX_PARAM_DATETIME = "datetime";

	public const DATABASE_NAME = "toe";

	public const MINIMUM_PASSWORD_LENGTH = 8;

	public const PARAMETER_KEY = 'params';

	public const DT_FORMAT = 'Y-m-d H:i:s';

	/**
	 * if ROLE_ALL is given to route: all incoming requests have access.
	 * if ROLE_ALL is given to user: has access to all routes
	 */
	public const ROLE_ALL = "*";

	public const ROLE_ADMIN       = 'admin';
	public const ROLE_ORGANIZER   = 'organizer';
	public const ROLE_MODERATOR   = 'moderator';
	public const ROLE_EDITOR      = 'editor';
	public const ROLE_PARTICIPANT = 'participant';
	public const ROLE_DRIVER      = 'driver';

	public const ROUTE_HOSTING_DIRECTORY = __DIR__ . "/../../public/route-files";

	public const EMAIL_RESET_LINK = "#/reset-password/";
}