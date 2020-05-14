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
		$this->s3->registerStreamWrapper();
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
		$file = $this->getS3FilePath($route->routeFilePath);
		$fp = fopen($file, 'r');
		if ($fp === null)
		{
			return false;
		}
		return $fp;
	}

	/**
	 * @inheritDoc
	 */
	public function getRouteFileUrl(string $savedRouteFileUrl)
	{
		$cmd = $this->s3->getCommand('GetObject', [
			'Bucket' => $this->bucket,
			'Key' => $savedRouteFileUrl
		]);
		$request = $this->s3->createPresignedRequest($cmd, '+8 hours');
		return (string)$request->getUri();
	}

	/**
	 * @inheritDoc
	 */
	public function deleteRouteFile(Route $route)
	{
		// TODO: Implement deleteRouteFile() method.
		$res = $this->s3->deleteObject([
			'Bucket' => $this->bucket,
			'Key' => $route->routeFilePath
		]);
		$route->fileWasDeleted();
		return $route;
	}

	/**
	 * @inheritDoc
	 */
	public function routeFileExists(Route $route)
	{
		if (!$route->hasFile())
		{
			return false;
		}
		$res = $this->s3->headObject([
			'Bucket' => $this->bucket,
			'Key' => $route->routeFilePath
		]);
		$length = $res->get('ContentLength');
		if (empty($length))
		{
			return false;
		}
		return (int)$length > 0;
	}

	/**
	 * Gets the s3 filepath based on the passed in route file path
	 *
	 * @param string $routeFilePath
	 *
	 * @return string
	 */
	private function getS3FilePath(string $routeFilePath)
	{
		return sprintf("s3://%s/%s", $this->bucket, $routeFilePath);
	}
}