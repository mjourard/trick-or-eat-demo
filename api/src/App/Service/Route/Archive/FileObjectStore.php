<?php
declare(strict_types=1);


namespace TOE\App\Service\Route\Archive;


use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileObjectStore implements iObjectStorage
{
	/**
	 * @var string The local directory in which route files are saved
	 */
	private $routeDir;

	/**
	 * FileObjectStore constructor.
	 *
	 * @param string                    $routeDir The directory on the local machine in which route files are stored
	 */
	public function __construct($routeDir)
	{
		$this->routeDir = $routeDir;
	}

	/**
	 * @inheritDoc
	 */
	public function saveRouteFile(UploadedFile $file, Route $route)
	{
		//upload the route to the server
		try
		{
			$file->move($this->routeDir, $route->routeFilePath);
		}
		catch (FileException $e)
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