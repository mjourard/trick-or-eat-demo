<?php
declare(strict_types=1);

namespace TOE\App\Service\AWS;


use Aws\S3\S3Client;

class S3Helper extends aWrapper
{
	/** @var S3Client */
	protected $client;

	public function __construct(array $configArgs)
	{
		$configArgs['version'] = '2006-03-01';
		parent::__construct($configArgs);
	}

	public function getClientClass()
	{
		return S3Client::class;
	}
}