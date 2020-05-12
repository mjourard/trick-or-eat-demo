<?php
declare(strict_types=1);

/**
 * A utility class for putting random functions that don't deserve their own class.
 * All methods here should be static
 *
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 5/17/2017
 * Time: 6:23 AM
 */

namespace TOE\GlobalCode;

class Util
{
	/**
	 * Removes all lines from a stacktrace that contain 'vendor' or 'phpunit' in them.
	 * Used to make debugging easier.
	 *
	 * @param $trace string An exception's stacktrace.
	 *
	 * @return string
	 */
	public static function removeFrameworkFromStacktrace($trace)
	{
		$trace = explode("\n", $trace);
		foreach ($trace as $key => &$line)
		{
			//removes vendor framework code from the stacktrace
			$number = substr($line, 0, stripos($line, ' ') + 1);
			if (preg_match('#[\\\\/]api[\\\\/]vendor[\\\\/]#', $line))
			{
				unset($trace[$key]);
				continue;
			}

			//removes phpunit code from the stacktrace
			if (preg_match('#[\\\\/]phpunit[\\\\/]#', $line))
			{
				unset($trace[$key]);
				continue;
			}

			$line = trim($line);
			$pattern = stripos($line, "internal function") === false ? '~(.*trickoreat)(.+)~i' : '~.*~i';

			preg_match($pattern, $line, $matches);

			if (count($matches) > 2)
			{
				$line = "$number " . $matches[2];
			}
		}

		return implode("\n", $trace);
	}
}