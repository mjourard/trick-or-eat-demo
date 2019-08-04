<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 10/28/2017
 * Time: 11:39 AM
 */

use TOE\Creds\clsCreds;
use TOE\GlobalCode\clsConstants;
use TOECron\clsDAL;
use TOECron\RegistrationImport\Team;
use TOECron\RegistrationImport\User;

require __DIR__ . '/../../vendor/autoload.php';

const REPORT_FILE = 'detail.csv';

const LAST_NAME = 'Last Name';
const FIRST_NAME = 'First Name';
const CITY = 'City';

const REGION = 'Province/State';
const COUNTRY = 'Country';
const EMAIL = 'Email';
const EVENT_ID = 'EventId';
const REG_DATE = 'Registration Date';
const TEAM_ID = 'Team ID';
const TEAM_NAME = 'Team Name';
const TEAM_CAPTAIN_NAME = 'Team Captain Name';
const TEAM_REG_DATE = 'Team Registration Date';

const DRY_RUN = false;
const GUELPH_EVENT_ID = 1;

$DAL = new clsDAL(clsCreds::DATABASE_USER, clsCreds::DATABASE_PASSWORD, clsConstants::DATABASE_HOST, clsConstants::DATABASE_NAME, clsConstants::DATABASE_PORT);
$filename = __DIR__ . '/' . REPORT_FILE;

/** @var User[] $users */
$users = [];

/** @var Team[] $teams */
$teams = [];

$emails = [];

$fp = fopen($filename, 'r');
if (!$fp)
{
	die("Could not read $filename\n");
}

//trim header
$keys = fgetcsv($fp);
if ($keys === false)
{
	die("Could not get header keys\n");
}
if (($keys = array_flip($keys)) === false)
{
	die("Could not flip the keys\n");
}

$skippableTeams = [
	'Meal Exchange Guelph',
	'.'
];

//parse the file into the objects we need
while (($line = fgetcsv($fp)) !== false)
{
	$skip = false;
	foreach ($skippableTeams as $badTeamName)
	{
		if ($line[$keys[TEAM_NAME]] === $badTeamName)
		{
			echo "Skipping a Line containnig the team '$badTeamName'\n";
			$skip = true;
		}
	}

	if ($skip)
	{
		continue;
	}


	$email = $line[$keys[EMAIL]];
	if (!isset($emails[$line[$keys[EMAIL]]]))
	{
		$emails[$line[$keys[EMAIL]]] = [
			'iterations' => 0,
			'names'      => []
		];
	}
	else
	{
		$email = uniqid($line[$keys[TEAM_NAME]]) . '@toeholder.com';
	}

	$user = new User(
		$line[$keys[FIRST_NAME]],
		$line[$keys[LAST_NAME]],
		$email,
		$line[$keys[REG_DATE]],
		DRY_RUN,
		$DAL
	);

	$emails[$line[$keys[EMAIL]]]['iterations']++;
	$emails[$line[$keys[EMAIL]]]['names'][] = $user->getFullName();

	$users[] = $user;
	if (!empty($line[$keys[TEAM_NAME]]))
	{
		$teamName = $line[$keys[TEAM_NAME]];
		if (!isset($teams[$teamName]))
		{
			$teams[$teamName] = new Team(
				$teamName,
				$line[$keys[TEAM_CAPTAIN_NAME]],
				$line[$keys[TEAM_REG_DATE]],
				DRY_RUN,
				$DAL
			);
		}
		$teams[$teamName]->addTeammate($user, $line[$keys[TEAM_REG_DATE]]);
	}
}
echo "finished reading\n";
echo count($users) . " users\n";
echo count($teams) . " teams\n";
fclose($fp);

$values = "";
foreach ($users as $user)
{
	$user->addUser();
	$user->registerForEvent(GUELPH_EVENT_ID);
}

foreach ($teams as $team)
{
	$team->createTeam(GUELPH_EVENT_ID);
}

