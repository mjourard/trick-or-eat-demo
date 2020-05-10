<?php
declare(strict_types=1);

namespace TOE\App\Service\User;


use TOE\GlobalCode\Constants;

class NewUser
{
	public $email;
	public $password;
	public $firstName;
	public $lastName;
	public $regionId;
	public $role;
	public $hearing;
	public $visual;
	public $mobility;

	public function __construct($email, $password, $firstName, $lastName, $regionId, $role = Constants::ROLE_PARTICIPANT, $hearing = false, $visual = false, $mobility = false)
	{
		$this->email = $email;
		$this->password = $password;
		$this->firstName = $firstName;
		$this->lastName = $lastName;
		$this->regionId = $regionId;
		$this->role = $role;
		$this->hearing = $hearing;
		$this->visual = $visual;
		$this->mobility = $mobility;
	}
}