<?php
declare(strict_types=1);


namespace TOE\App\Service\AWS;


use Aws\RDSDataService\Exception\RDSDataServiceException;
use Aws\RDSDataService\RDSDataServiceClient;
use TOE\GlobalCode\Constants;
use TOETests\clsTestConstants;

class AuroraDataAPIWrapper
{
	public const TYPE_HINT_DATE = 'DATE';
	public const TYPE_HINT_DECIMAL = 'DECIMAL';
	public const TYPE_HINT_TIME = 'TIME';
	public const TYPE_HINT_TIMESTAMP = 'TIMESTAMP';
	/**
	 * @var RDSDataServiceClient
	 */
	private $client;
	/**
	 * @var string The database that will be passed to each sql query
	 */
	private $database;
	/**
	 * @var string
	 */
	private $arn;
	/**
	 * @var string
	 */
	private $secretArn;

	public function __construct(RDSDataServiceClient $client)
	{
		$this->client = $client;
	}

	/**
	 * @param string $database
	 *
	 * @return AuroraDataAPIWrapper
	 */
	public function setDatabase($database)
	{
		$this->database = $database;

		return $this;
	}

	/**
	 * @param string $arn The arn of the database to connect to
	 *
	 * @return AuroraDataAPIWrapper
	 */
	public function setDbArn($arn)
	{
		$this->arn = $arn;

		return $this;
	}

	public function setSecretArn($arn)
	{
		$this->secretArn = $arn;

		return $this;
	}

	/**
	 * Gets the AWS ARN value of the aurora serverless cluster being connected to
	 *
	 * @return string
	 */
	public function getArn()
	{
		return $this->arn;
	}

	/**
	 * Ges the AWS ARN value of the SSM secret being used during connection to the data api
	 *
	 * @return string
	 */
	public function getSecretArn()
	{
		return $this->secretArn;
	}

	/**
	 * @param string $sql
	 * @param array  $parameters
	 * @param array  $configOverride
	 *
	 * @return \Aws\Result
	 * @throws RDSDataServiceException When there is an error querying the database. Could be that the database is not spun up
	 *		Possible errors:
	 *                                 BadRequestException
	 *                                 StatementTimeoutException
	 *                                 InternalServerErrorException
	 *                                 ForbiddenException
	 *                                 ServiceUnavailableError
	 */
	public function executeStatement(string $sql, $parameters = [], $configOverride = [])
	{
		$options = [
			'continueAfterTimeout'  => false,
			'database'              => $this->database,
			'includeResultMetadata' => true,
			'parameters'            => $parameters,
			'resourceArn'           => $this->arn,
			'resultSetOptions'      => [
				'decimalReturnType' => 'DOUBLE_OR_LONG',
			],
			'schema'                => $this->database,
			'secretArn'             => $this->secretArn,
			'sql'                   => $sql
		];
		foreach($configOverride as $option => $value)
		{
			if(isset($options[$option]))
			{
				$options[$option] = $value;
			}
		}

		return $this->client->executeStatement($options);
	}

	/**
	 * Queries the aurora database and returns an array of associative arrays of the records queried
	 *
	 * @param string $sql
	 * @param array  $parameters
	 * @param array  $configOverride
	 *
	 * @return array
	 */
	public function queryDB(string $sql, $parameters = [], $configOverride = [])
	{
		$res = $this->executeStatement($sql, $parameters, $configOverride);
		return $this->getQueryRecords($res->get('columnMetadata'), $res->get('records'));
	}

	/**
	 * converts the passed in parameters to the array that is expected by the aurora api.
	 *
	 * @param array $parameters
	 * @param array $parameterTypes
	 *
	 * @return array
	 */
	public function convertDoctrineParams($parameters, $parameterTypes)
	{
		$params = [];
		foreach($parameters as $name => $value)
		{
			$newParam = [
				'name' => $name,
			];
			$typeHint = null;
			$paramType = isset($parameterTypes[$name]) ? $parameterTypes[$name] : Constants::SILEX_PARAM_INT;
			switch($paramType)
			{
				case Constants::SILEX_PARAM_STRING:
					$newParam['value'] = ['stringValue' => $value];
					break;
				case Constants::SILEX_PARAM_INT:
				default:
					$newParam['value'] = ['longValue' => $value];
			}
			$params[] = $newParam;
		}

		return $params;
	}

	/**
	 * Starts a transaction that must be committed or rolled back before a table can be modified again
	 *
	 * Transactions are automatically rolled back 24 hours after they've been started
	 *
	 * @return string
	 */
	public function beginTransaction()
	{
		$options = [
			'database'    => $this->database,
			'resourceArn' => $this->arn,
			'schema'      => $this->database,
			'secretArn'   => $this->secretArn
		];
		$res = $this->client->beginTransaction($options);

		return $res->get('transactionId');
	}

	/**
	 * @param string $transactionId
	 *
	 * @return string The status of the commit operation
	 */
	public function commitTransaction($transactionId)
	{
		$options = [
			'resourceArn'   => $this->arn,
			'secretArn'     => $this->secretArn,
			'transactionId' => $transactionId
		];
		$res = $this->client->commitTransaction($options);

		return $res->get('transactionStatus');
	}

	/**
	 * @param string $transactionId
	 *
	 * @return string The status of the rollback operation
	 */
	public function rollbackTransaction($transactionId)
	{
		$options = [
			'resourceArn'   => $this->arn,
			'secretArn'     => $this->secretArn,
			'transactionId' => $transactionId
		];
		$res = $this->client->rollbackTransaction($options);
		return $res->get('transactionStatus');
	}

	/**
	 * Gets an associative array of the returned records when a select statement was passed to Aurora
	 *
	 * @param array $columnMetadata
	 * @param array $records
	 *
	 * @return array
	 */
	private function getQueryRecords($columnMetadata, $records)
	{
		if (empty($columnMetadata) || empty($records))
		{
			return [];
		}
		$res = [];
		$columns = [];
		foreach($columnMetadata as $column)
		{
			$columns[] = $column['label'];
		}
		foreach($records as $record)
		{
			$newRecord = [];
			foreach($record as $idx => $value)
			{
				$val = null;
				if (isset($value['doubleValue']))
				{
					$val = $value['doubleValue'];
				}
				if (isset($value['isNull']))
				{
					$val = $value['isNull'] ? null : '';
				}
				if (isset($value['longValue']))
				{
					$val = $value['longValue'];
				}
				if (isset($value['stringValue']))
				{
					$val = $value['stringValue'];
				}
				$newRecord[$columns[$idx]] = $val;
			}
			$res[] = $newRecord;
		}
		return $res;
	}
}