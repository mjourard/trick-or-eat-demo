<?php


namespace TOE\App\Service\Email;


use Throwable;

class EmailException extends \Exception
{
	private $sourceException;

	public function __construct($message = "", $sourceException = null, $code = 0, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
		$this->sourceException = $sourceException;
	}

	public function getSourceException()
	{
		return $this->sourceException;
	}
}