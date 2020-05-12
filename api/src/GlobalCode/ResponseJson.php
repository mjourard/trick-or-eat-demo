<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: LENOVO-T430
 * Date: 11/8/2016
 * Time: 10:41 PM
 */

namespace TOE\GlobalCode;

class ResponseJson
{
	public static function getJsonResponseArray($success, $message, $extra = null)
	{
		$array = [
			"success" => $success,
			"message" => $message
		];

		if (!empty($extra))
		{
			$array = array_merge($array, $extra);
		}
		return $array;
	}
}