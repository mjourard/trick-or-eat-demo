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
	 * @var string The prefix of urls in which files should be served as
	 */
	private $routefilesUrlPrefix;

	/**
	 * FileObjectStore constructor.
	 *
	 * @param string $routeDir The directory on the local machine in which route files are stored
	 * @param string $routefilesUrlPrefix The prefix to be attached to urls of served route files. Should contain the protocol, domain and any required file paths
	 */
	public function __construct($routeDir, $routefilesUrlPrefix)
	{
		$this->routeDir = $routeDir;
		$this->routefilesUrlPrefix = $routefilesUrlPrefix;
	}

	/**
	 * @inheritDoc
	 */
	public function saveRouteFile(UploadedFile $file, Route $route)
	{
		//check if the file already exists
		if (file_exists($this->routeDir . DIRECTORY_SEPARATOR . $route->routeFilePath))
		{
			throw new RouteManagementException("File already exists. Ensure you want to overwrite it...");
		}
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
		if (!$route->hasFile())
		{
			throw new RouteManagementException("route object does not have a file assigned to it");
		}
		$file = $this->getRouteFilepath($route);
		if (!file_exists($file))
		{
			throw new RouteManagementException("Unable to find file $file");
		}
		$fp =  fopen($file, 'r');
		if ($fp === null)
		{
			return false;
		}
		return $fp;
	}

	/**
	 * @inheritDoc
	 */
	public function deleteRouteFile(Route $route)
	{
		$file = $this->getRouteFilepath($route);
		if (file_exists($file))
		{
			unlink($file);
		}
		$route->fileWasDeleted();
		return $route;
	}

	/**
	 * Checks if the route file exists and is readable
	 *
	 * @param Route $route
	 *
	 * @return bool
	 */
	public function routeFileExists(Route $route)
	{
		if (!$route->hasFile())
		{
			return  false;
		}
		$file = $this->getRouteFilepath($route);
		return file_exists($file) && is_readable($file);
	}

	/**
	 * Gets the local filepath of the route object
	 *
	 * @param Route $route
	 *
	 * @return string|false
	 */
	private function getRouteFilepath(Route $route)
	{
		if (!$route->hasFile())
		{
			return false;
		}
		return  $this->routeDir . $route->routeFilePath;
	}

	/**
	 * @inheritDoc
	 */
	public function getRouteFileUrl(string $savedRouteFileUrl)
	{
		return $this->routefilesUrlPrefix . $savedRouteFileUrl;
	}
}