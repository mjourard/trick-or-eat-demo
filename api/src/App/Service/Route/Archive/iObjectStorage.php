<?php
declare(strict_types=1);


namespace TOE\App\Service\Route\Archive;


use Symfony\Component\HttpFoundation\File\UploadedFile;

interface iObjectStorage
{
	/**
	 * Saves the passed in file using the metadata of the Route object
	 *
	 * @param UploadedFile $file
	 * @param Route        $route
	 *
	 * @return Route Will return the Route object with updated properties
	 * @throws RouteManagementException On error of saving the object
	 */
	public function saveRouteFile(UploadedFile $file, Route $route);

	/**
	 * Retrieves the route file
	 *
	 * @param Route $route The route file to retrieve
	 *
	 * @return resource|false returns a stream resource of the data of the route file, or false if the file could not be found
	 */
	public function getRouteFile(Route $route);

	/**
	 * Deletes the passed in Route object
	 *
	 * @param Route $route
	 *
	 * @return Route an updated Route object
	 */
	public function deleteRouteFile(Route $route);

	/**
	 * Checks if the file associated with the route object can be found
	 *
	 * @param Route $route
	 *
	 * @return bool true if the file can be found, false otherwise
	 */
	public function routeFileExists(Route $route);
}