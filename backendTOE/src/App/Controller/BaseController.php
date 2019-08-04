<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 1/30/2017
 * Time: 12:51 PM
 */

namespace TOE\App\Controller;

use Monolog\Processor\WebProcessor;
use Silex\Application;
use TOE\GlobalCode\clsConstants;
USE TOE\GlobalCode\clsHTTPCodes;

class BaseController
{
	/** @var  \TOE\App\Service\UserProvider */
	protected $userInfo = null;

	/** @var  \Doctrine\DBAL\Connection */
	protected $db = null;

	/** @var \Silex\Application */
	protected $app = null;

	/** @var  \Monolog\Logger */
	protected $logger = null;

	/**
	 * Sets the local params to allow code completion via annotation
	 *
	 * @param \Silex\Application $app
	 */
	protected function InitializeInstance(Application $app)
	{
		$this->app = $app;
		$this->db = $app['db'];
		$this->logger = $app['monolog'];
		if ($app['user_info']->GetToken() !== null)
		{
			$this->userInfo = $app['user'];
		}
	}

	/**
	 * Checks the user info for any of the permitted roles that can use the called controller function
	 *
	 * @param array $permittedRoles Array of the roles permitted.
	 */
	protected function UnauthorizedAccess(array $permittedRoles)
	{
		//change permitted roles to associative array
		$permittedRoles = array_flip($permittedRoles);

		if ($this->userInfo === null)
		{
			$this->app->abort(clsHTTPCodes::CLI_ERR_NOT_AUTHORIZED, "Unauthorized access - no info");
		}

		if (isset($permittedRoles[clsConstants::ROLE_ALL]))
		{
			return;
		}

		foreach ($this->userInfo->getUserRoles() as $role)
		{
			if ($role === clsConstants::ROLE_ALL)
			{
				return;
			}

			if (isset($permittedRoles[$role]))
			{
				return;
			}
		}

		$this->app->abort(clsHTTPCodes::CLI_ERR_NOT_AUTHORIZED, "Unauthorized access");
	}
}