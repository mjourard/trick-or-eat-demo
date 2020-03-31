<?php


namespace TOE\App;


use Throwable;

class DALException extends \Exception
{
	/**
	 * @var string
	 */
	private $query;
	/**
	 * @var array
	 */
	private $values;

	public function __construct(string $query, array $values, $message = "", $code = 0, Throwable $previous = null)
	{
		$this->query = $query;
		$this->values = $values;
		parent::__construct($message, $code, $previous);
	}

	/**
	 * @return string
	 */
	public function GetQuery()
	{
		return $this->query;
	}

	/**
	 * @return array
	 */
	public function GetValues()
	{
		return $this->values;
	}
}