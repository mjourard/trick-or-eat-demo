<?php
/**
 * For direct password entry
 *
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 10/31/2017
 * Time: 5:11 PM
 */

use TOE\Creds\clsCreds;
use TOE\GlobalCode\clsConstants;
use TOECron\clsDAL;

require __DIR__ . '/../vendor/autoload.php';

const DRY_RUN = true;

if (count($argv) < 2)
{
	die("Usage: "  . basename(__FILE__) . " <email> <new-password>");
}

$DAL = new clsDAL(clsCreds::DATABASE_USER, clsCreds::DATABASE_PASSWORD, '127.0.0.1', clsConstants::DATABASE_NAME);
$username = $DAL->EscapeString($argv[1]);
$password = password_hash($argv[2], PASSWORD_DEFAULT);


$query = "
	SELECT user_id
	FROM 'user'
	WHERE email = '$username'
";
$results = $DAL->Query($query);
if (DRY_RUN)
{
	var_dump($results);
}

if (count($results) !== 1)
{
	die("Could not find user $username or the database is compriomised\n");
}

$query = "
UPDATE user SET
password = $password
where user_id = {$results[0]['user_id']}
";

if (DRY_RUN)
{
	die($query);
}

$results = $DAL->ExecuteNonQuery($query);
var_dump($results);
