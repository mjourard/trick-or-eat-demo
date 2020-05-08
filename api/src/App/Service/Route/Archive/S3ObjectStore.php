<?php
declare(strict_types=1);


namespace TOE\App\Service\Route\Archive;


use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class S3ObjectStore implements iObjectStorage
{
	/**
	 * @var S3Client The local directory in which route files are saved
	 */
	private $s3;
	/**
	 * @var string The bucket in which route files are stored
	 */
	private $bucket;

	/**
	 * S3ObjectStore constructor.
	 *
	 * @param S3Client $s3
	 * @param string   $routeBucket
	 */
	public function __construct(S3Client $s3, $routeBucket)
	{
		$this->s3 = $s3;
		$this->bucket = $routeBucket;
	}

	/**
	 * @inheritDoc
	 */
	public function saveRouteFile(UploadedFile $file, Route $route)
	{
		//upload the route to S3
		try
		{
			$this->s3->putObject([
				'Bucket' => $this->bucket,
				'Key' => $route->routeFilePath,
				'SourceFile' => $file->getRealPath()
			]);
		}
		catch (S3Exception $e)
		{
			throw new RouteManagementException($e->getMessage());
		}
		return $route;
	}

	/**
	 * @inheritDoc
	 */
	public function getRouteFile(Route $route)
	{
		// TODO: Implement getRouteFile() method.
	}

	/**
	 * @inheritDoc
	 */
	public function deleteRouteFile(Route $route)
	{
		// TODO: Implement deleteRouteFile() method.
	}
}