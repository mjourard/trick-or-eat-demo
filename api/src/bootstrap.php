<?php
declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: LENOVO-T430
 * Date: 11/11/2016
 * Time: 9:59 AM
 */

use TOE\GlobalCode\Env;

require_once __DIR__ . '/../vendor/autoload.php';

if (Env::get(Env::TOE_DONT_USE_DOTENV) !== 'true')
{
	$dotenv = new \Symfony\Component\Dotenv\Dotenv(true);
	$dotenv->loadEnv(__DIR__ . '/../.env');
}