<?php
/**
 * Created by PhpStorm.
 * User: Matthew Jourard
 * Date: 2/27/2017
 * Time: 5:57 PM
 */

namespace TOE\App\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsResponseJson;

class RouteArchiveController extends BaseController
{
	/**
	 * Adds a route to the database. Passes the kmz file to wherever we are hosting our routes
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Silex\Application                        $app
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function addRoute(Request $request, Application $app)
	{
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER]);

		if (empty($request->files))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "No files received to upload."));
		}

		/* @var $file \Symfony\Component\HttpFoundation\File\UploadedFile */
		$file = $request->files->get("file");
		$fileName = $this->getRouteName($app[clsConstants::PARAMETER_KEY]['zone_id'], $file->getClientOriginalName());
		$hostName = $this->getRouteHostingUrl($app[clsConstants::PARAMETER_KEY]['zone_id'], $file->getClientOriginalName());

		//verify the route can be added to the database
		$qb = $this->db->createQueryBuilder();
		$qb->select('route_id')
			->from('ROUTE_ARCHIVE')
			->where('route_name = :name')
			->andWhere('zone_id = :zoneId')
			->andWhere('owner_user_id = ' . $this->userInfo->getID())
			->setParameter(':name', basename($fileName), clsConstants::SILEX_PARAM_STRING)
			->setParameter(':zoneId', $app[clsConstants::PARAMETER_KEY]['zone_id']);

		if (!empty($qb->execute()->fetchAll()))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "You already have a route '$fileName' in zone {$app[clsConstants::PARAMETER_KEY]['zone_id']}"));
		}

		//upload the route to the server
		try
		{
			$file->move(clsConstants::ROUTE_HOSTING_DIRECTORY, $hostName);
		}
		catch (FileException $e)
		{
			$this->logger->error($e->getMessage() . "\n" . $e->getTraceAsString());
			return $app->json(clsResponseJson::GetJsonResponseArray(false, $e->getMessage()));
		}

		//add the record to the database
		$qb = $this->db->createQueryBuilder();
		$qb->insert('ROUTE_ARCHIVE')
			->values([
				'route_file_url'        => ':image',
				'route_name'            => ':name',
				'required_people'       => clsConstants::MAX_ROUTE_MEMBERS,
				'type'                  => ':type',
				'wheelchair_accessible' => ':mobile',
				'blind_accessible'      => ':visual',
				'hearing_accessible'    => ':hearing',
				'zone_id'               => ':zone_id',
				'owner_user_id'         => $this->userInfo->getID()
			])
			->setParameter(':image', $hostName)
			->setParameter(':name', basename($fileName))
			->setParameter(':type', $app[clsConstants::PARAMETER_KEY]['type'])
			->setParameter(':mobile', $app[clsConstants::PARAMETER_KEY]['mobility'])
			->setParameter(':visual', $app[clsConstants::PARAMETER_KEY]['visual'])
			->setParameter(':hearing', $app[clsConstants::PARAMETER_KEY]['hearing'])
			->setParameter(':zone_id', $app[clsConstants::PARAMETER_KEY]['zone_id']);

		$qb->execute();

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "Upload Successful"));
	}

	/**
	 * Deletes a route from the database and from where we are hosting the kmz files
	 *
	 * @param \Silex\Application $app
	 *
	 * @param  int               $zoneId The id of the zone you of the route you want to delete
	 * @param  int               $routeId The id of the route you want to delete
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function deleteRoute(Application $app, $zoneId, $routeId)
	{
		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER]);
		$qb = $this->db->createQueryBuilder();
		$qb->select('route_name')
			->from('ROUTE_ARCHIVE')
			->where('zone_id = :zoneId')
			->andWhere('route_id = :routeId')
			->setParameter(':zoneId', $zoneId, clsConstants::SILEX_PARAM_INT)
			->setParameter(':routeId', $routeId, clsConstants::SILEX_PARAM_INT);

		$row = $qb->execute()->fetch();

		if (empty($row))
		{
			return $app->json(clsResponseJson::GetJsonResponseArray(false, "No route found with Zone ID of $zoneId and route id of $routeId"));
		}

		$name = $row['route_name'];

		$qb = $this->db->createQueryBuilder();
		$qb->delete('ROUTE_ARCHIVE')
			->where('zone_id = :zoneId')
			->andWhere('route_id = :routeId')
			->setParameter(':zoneId', $zoneId, clsConstants::SILEX_PARAM_INT)
			->setParameter(':routeId', $routeId, clsConstants::SILEX_PARAM_INT);

		$qb->execute();

		unlink(clsConstants::ROUTE_HOSTING_DIRECTORY . "/$name");

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "Route $name removed"));
	}

	/**
	 * Returns a list of route_archive entities that are needed to display the route information.
	 *
	 * @param \Silex\Application                        $app
	 * @param  int                                      $zoneId
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function getRoutes(Application $app, $zoneId)
	{

		$this->InitializeInstance($app);
		$this->UnauthorizedAccess([clsConstants::ROLE_ADMIN, clsConstants::ROLE_ORGANIZER]);
		$qb = $this->db->createQueryBuilder();
		$qb->select(
			'ra.route_id',
			'ra.route_name',
			'z.zone_name',
			'ra.wheelchair_accessible',
			'ra.blind_accessible',
			'ra.hearing_accessible'
		)
			->from('ROUTE_ARCHIVE', 'ra')
			->leftJoin('ra', 'zone', 'z', 'ra.zone_id = z.zone_id')
			->where('ra.zone_id = :zone_id')
			->setParameter('zone_id', $zoneId, clsConstants::SILEX_PARAM_INT);

		$routes = $qb->execute()->fetchAll();

		foreach ($routes as &$route)
		{
			$route['wheelchair_accessible'] = $route['wheelchair_accessible'] === "true" ? true : false;
			$route['blind_accessible'] = $route['blind_accessible'] === "true" ? true : false;
			$route['hearing_accessible'] = $route['hearing_accessible'] === "true" ? true : false;
		}

		return $app->json(clsResponseJson::GetJsonResponseArray(true, "", ["routes" => $routes]));
	}

	private function getRouteName($zoneId, $imageName)
	{
		return "/$zoneId-" . str_replace(" ", "_", $imageName);
	}

	private function getRouteHostingUrl($zoneId, $fileName)
	{
		$ext = "";
		$info = pathinfo($fileName);
		if (!empty($info) && isset($info['extension']))
		{
			$ext = $info['extension'];
		}
		return uniqid("/$zoneId-") . ".$ext";
	}
}