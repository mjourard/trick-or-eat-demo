<?php
declare(strict_types=1);

namespace TOE\App\Service;


use Throwable;

class NotYetImplementedException extends \Exception
{
	public const MESSAGE_FORMAT = "Feature '%s' not yet implemented";
	public function __construct($featureName, $message = "", $code = 0, Throwable $previous = null)
	{
		$msg = sprintf(self::MESSAGE_FORMAT, $featureName) . $message;
		if (!empty($message))
		{
			$msg .= ". Additional message: $message";
		}
		parent::__construct($msg, $code, $previous);
	}
}