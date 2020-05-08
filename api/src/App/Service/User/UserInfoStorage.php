<?php
namespace TOE\App\Service\User;

class UserInfoStorage
{
	/* @var $token Object */
	private $token;

	/** decToken is a decoded token
	 * a decoded token is a ready-to-use JSON object
	 *
	 * @param Object $decToken
	 */
	public function __construct($decToken = null)
	{
		$this->token = null;

		if ($decToken !== null)
		{
			$this->token = $decToken;
		}
	}

	public function getToken()
	{
		return $this->token;
	}

	public function setToken($token)
	{
		$this->token = $token;
	}
}
