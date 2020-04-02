<?php
/**
 * Created by PhpStorm.
 * User: LENOVO-T430
 * Date: 11/11/2016
 * Time: 9:59 AM
 */

use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsEnv;

require_once __DIR__ . '/../vendor/autoload.php';

if (clsEnv::Get(clsEnv::TOE_STAGE) === clsConstants::TOE_STAGE_LOCAL)
{
	$dotenv = new \Symfony\Component\Dotenv\Dotenv(true);
	$dotenv->load(__DIR__ . '/../.env');
}