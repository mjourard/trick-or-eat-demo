<?php
namespace TOE\App\Service;

class UserProvider
{
	private $email = null;

	private $id = null;

	private $userRoles = null;

	/* decToken is a decoded token
	 * a decoded token is a ready-to-use JSON object
	 */

	public function __construct($decToken)
	{
		if ($decToken !== null)
		{
			$this->email = $decToken->data->email;
			$this->id = $decToken->data->userId;
			$this->userRoles = $decToken->data->userRoles;
		}
	}

	public function getEmail()
	{
		return $this->email;
	}

	public function getID()
	{
		return $this->id;
	}

	/**
	 * @return array An array of strings representing the user's roles.
	 */
	public function getUserRoles()
	{
		return $this->userRoles;
	}

	/**
	 * @param String $passedRole One of the ROLE_ constants in clsConstants.
	 *
	 * @return bool returns true if the user has the passed in role assigned to them. False otherwise.
	 *              Should not be used to authorize user roles, as that is built into the BaseController class.
	 */
	public function hasRole($passedRole)
	{
		if (!is_array($this->userRoles))
		{
			return false;
		}

		foreach ($this->userRoles as $userRole)
		{
			if ($userRole === $passedRole)
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array Returns an associative array. Keys are 'email', 'id' and 'user_roles'
	 */
	public function toArray()
	{
		return [
			'email'      => $this->email,
			'id'         => $this->id,
			'user_roles' => $this->userRoles
		];
	}
}

?>
