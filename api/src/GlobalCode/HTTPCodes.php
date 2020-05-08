<?php
declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: LENOVO-T430
 * Date: 11/8/2016
 * Time: 11:58 PM
 */

namespace TOE\GlobalCode;


class HTTPCodes
{
	/* successful responses */
	public const SUCCESS_DATA_RETRIEVED = 200;
	public const SUCCESS_RESOURCE_CREATED = 201;
	public const SUCCESS_ACCEPTED_BUT_NOT_COMPLETED = 202;
	public const SUCCESS_NO_CONTENT = 204;

	/* client error */
	public const CLI_ERR_BAD_REQUEST = 400;
	public const CLI_ERR_AUTH_REQUIRED = 401;
	public const CLI_ERR_NOT_AUTHORIZED = 403;
	public const CLI_ERR_NOT_FOUND = 404;
	public const CLI_ERR_ACTION_NOT_ALLOWED = 405;
	public const CLI_ERR_REQUEST_TIMEOUT = 408;
	public const CLI_ERR_CONFLICT = 409;
	public const CLI_ERR_SPECIFIC_USER_REQUEST_OVERLOAD = 429;

	/* server error */

	public const SERVER_GENERIC_ERROR = 500;
	public const SERVER_SERVICE_UNAVAILABLE = 503;
	public const SERVER_ERROR_GENERIC_DATABASE_FAILURE = 512;
}
