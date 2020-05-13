<?php
declare(strict_types=1);

namespace TOE\App\Service\AWS;


abstract class aWrapper
{
	/** @var  \Aws\AwsClient */
	protected $client;

	public function __construct(array $configArgs)
	{
		$args = [
			'version' => '2010-12-01',
			'region'  => 'us-east-1'
		];
		if (!empty($configArgs['version']))
		{
			$args['version'] = $configArgs['version'];
		}
		if (!empty($configArgs['region']))
		{
			$args['region'] = $configArgs['region'];
		}
		if (!empty($configArgs['RoleArn']))
		{
			$args['profile'] = 'default';
			$args['RoleArn'] = $configArgs['RoleArn'];
		}
		if (!empty($configArgs['key']) && !empty($configArgs['secret']))
		{
			$args['credentials'] = [
				'key' => $configArgs['key'],
				'secret' => $configArgs['secret']
			];
		}
		$clientClass = $this->getClientClass();
		$this->client = new $clientClass($args);
	}

	/**
	 * Gets the name of an AWS Client class that will be initialized
	 *
	 * @return string
	 */
	abstract public function getClientClass();

	/**
	 * @return \Aws\AwsClient
	 */
	public function getClient()
	{
		return $this->client;
	}
}