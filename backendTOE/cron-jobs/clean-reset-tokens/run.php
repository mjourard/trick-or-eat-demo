<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 8/14/2017
 * Time: 12:11 PM
 *
 * ## CLEAN RESET TOKEN
 *
 * The purpose of this cron job is to delete expired reset tokens from the database.
 *
 * It should be run every 15 minutes on the production database.
 */

require __DIR__ . '/../../src/GlobalCode/clsConstants.php';
require __DIR__ . '/../clsDAL.php';

use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsEnv;
use TOECron\clsDAL;

//get a connection to the backend
$DAL = new clsDAL(
	clsEnv::Get(clsEnv::TOE_DATABASE_USER),
	clsEnv::Get(clsEnv::TOE_DATABASE_PASSWORD),
	clsEnv::Get(clsEnv::TOE_DATABASE_HOST),
	clsConstants::DATABASE_NAME,
	clsEnv::Get(clsEnv::TOE_DATABASE_PORT)
);

//get the current time
$time = time();

//delete all expired tokens
$del = "
DELETE FROM password_request
WHERE NOT unique_id = ''  
AND (expired_at < $time
OR status = 'used')
";

$DAL->ExecuteNonQuery($del);
