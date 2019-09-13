<?php
/**
 * Created by PhpStorm.
 * User: LENOVO-T430
 * Date: 11/13/2016
 * Time: 1:54 PM
 */

namespace TOETests;

class clsTesterCreds
{

	const SUPER_ADMIN_EMAIL    = "admin@toetests.com";
	const SUPER_ADMIN_PASSWORD = "Password1";

	const ORGANIZER_EMAIL    = "organizer@toetests.com";
	const ORGANIZER_PASSWORD = "Password1";

	const NORMAL_USER_EMAIL    = "normaluser@toetests.com";
	const NORMAL_USER_PASSWORD = "Password1";

	const GENERIC_PASSWORD = 'Password1';

	const ADMIN_REGISTERED_EMAIL                          = 'admin_registered_for_event@toetests.com';
	const NORMAL_USER_REGISTERED_EMAIL                    = 'user_registered_for_event@toetests.com';
	const ORGANIZER_REGISTERED_EMAIL                      = 'organizer_registered_for_event@toetests.com';
	const ADMIN_ON_TEAM_EMAIL                             = 'admin_on_team@toetests.com';
	const NORMAL_USER_ON_TEAM_AS_CAPTAIN_EMAIL            = 'user_on_team_as_captain@toetests.com';
	const NORMAL_USER_ON_TEAM_EMAIL                       = 'user_on_team@toetests.com';
	const ORGANIZER_ON_TEAM_EMAIL                         = 'organizer_on_team@toetests.com';
	const ADMIN_ON_TEAM_WITH_ROUTE_EMAIL                  = 'admin_on_team_with_route@toetests.com';
	const NORMAL_USER_ON_TEAM_WITH_ROUTE_AS_CAPTAIN_EMAIL = 'user_on_team_as_captain_with_route@toetests.com';
	const NORMAL_USER_ON_TEAM_WITH_ROUTE_EMAIL            = 'user_on_team_with_route@toetests.com';
	const ORGANIZER_ON_TEAM_WITH_ROUTE_EMAIL              = 'organizer_on_team_with_route@toetests.com';
	const NORMAL_USER_ON_TEAM_OF_ONE_AS_CAPTAIN_EMAIL     = 'user_on_team_of_one_as_captain@toetests.com';
	const NORMAL_USER_ON_TEAM_OF_EMPTY_AS_CAPTAIN_EMAIL   = 'user_on_team_of_empty_as_captain@toetests.com';
	const NORMAL_USER_REGISTERED_OTHER_EVENT_EMAIL        = 'user_registered_for_other_event@toetests.com';

	const CREDS = [
		self::SUPER_ADMIN_EMAIL                               => self::SUPER_ADMIN_PASSWORD,
		self::ORGANIZER_EMAIL                                 => self::ORGANIZER_PASSWORD,
		self::NORMAL_USER_EMAIL                               => self::NORMAL_USER_PASSWORD,
		self::ADMIN_REGISTERED_EMAIL                          => self::GENERIC_PASSWORD,
		self::NORMAL_USER_REGISTERED_EMAIL                    => self::GENERIC_PASSWORD,
		self::ORGANIZER_REGISTERED_EMAIL                      => self::GENERIC_PASSWORD,
		self::ADMIN_ON_TEAM_EMAIL                             => self::GENERIC_PASSWORD,
		self::NORMAL_USER_ON_TEAM_AS_CAPTAIN_EMAIL            => self::GENERIC_PASSWORD,
		self::NORMAL_USER_ON_TEAM_EMAIL                       => self::GENERIC_PASSWORD,
		self::ORGANIZER_ON_TEAM_EMAIL                         => self::GENERIC_PASSWORD,
		self::ADMIN_ON_TEAM_WITH_ROUTE_EMAIL                  => self::GENERIC_PASSWORD,
		self::NORMAL_USER_ON_TEAM_WITH_ROUTE_AS_CAPTAIN_EMAIL => self::GENERIC_PASSWORD,
		self::NORMAL_USER_ON_TEAM_WITH_ROUTE_EMAIL            => self::GENERIC_PASSWORD,
		self::ORGANIZER_ON_TEAM_WITH_ROUTE_EMAIL              => self::GENERIC_PASSWORD,
		self::NORMAL_USER_ON_TEAM_OF_ONE_AS_CAPTAIN_EMAIL     => self::GENERIC_PASSWORD,
		self::NORMAL_USER_ON_TEAM_OF_EMPTY_AS_CAPTAIN_EMAIL   => self::GENERIC_PASSWORD,
		self::NORMAL_USER_REGISTERED_OTHER_EVENT_EMAIL        => self::GENERIC_PASSWORD
	];
}