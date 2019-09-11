<?php

/**
 * Created by PhpStorm.
 * User: LENOVO-T430
 * Date: 11/8/2016
 * Time: 11:58 PM
 */

namespace TOE\GlobalCode;


class clsHTTPCodes
{
	/* successful responses */
	const SUCCESS_DATA_RETRIEVED                     = 200;
	const SUCCESS_RESOURCE_CREATED                   = 201;
	const SUCCESS_REQUEST_ACCEPTED_BUT_NOT_COMPLETED = 202;
	const SUCCESS_NO_CONTENT                         = 204;

	/* client error */
	const CLI_ERR_BAD_REQUEST                    = 400;
	const CLI_ERR_AUTH_REQUIRED                  = 401;
	const CLI_ERR_NOT_AUTHORIZED                 = 403;
	const CLI_ERR_NOT_FOUND                      = 404;
	const CLI_ERR_ACTION_NOT_ALLOWED             = 405;
	const CLI_ERR_REQUEST_TIMEOUT                = 408;
	const CLI_ERR_CONFLICT                       = 409;
	const CLI_ERR_SPECIFIC_USER_REQUEST_OVERLOAD = 429;

	/* server error */

	const SERVER_GENERIC_ERROR                  = 500; //TODO remove this, leading to bad practice...
	const SERVER_SERVICE_UNAVAILABLE            = 503;
	const SERVER_ERROR_GENERIC_DATABASE_FAILURE = 512;
}
