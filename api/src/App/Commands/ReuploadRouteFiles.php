<?php
declare(strict_types=1);

namespace TOE\App\Commands;


use Aws\S3\S3Client;
use Doctrine\DBAL\Connection;
use Silly\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use TOE\App\Service\AWS\S3Helper;
use TOE\App\Service\AWSConfig;
use TOE\App\Service\Route\Archive\Route;
use TOE\App\Service\Route\Archive\RouteManager;
use TOE\App\Service\Route\Archive\S3ObjectStore;
use TOE\GlobalCode\Env;

class ReuploadRouteFiles extends aCmd
{
	protected static $defaultName = 'reupload-route-files';

	protected function configure()
	{
		$this->setDescription('Reads through the passed in directory for .kmz and .kml files. Searches the db for matching route_name rows and reuploads them to S3 with the matched route_file_url');
		$this->addArgument('directory', InputArgument::REQUIRED, 'A local directory containing the .kmz and .kml route files to reupload');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->notifyUserOfDBType($output);
		$dir = realpath($input->getArgument('directory'));
		try
		{
			$routeFiles = $this->getRouteFiles($dir);
		}
		catch(\Exception $ex)
		{
			$output->writeln($ex->getMessage() . "| Exiting...");

			return 1;
		}

		$map = $this->getRouteUrlMap(2, $routeFiles);
		$s3 = $this->getS3ObjectStorage();
		foreach($map as $routeFile => $route)
		{
			/** @var Route $route */
			$filepath = $dir . DIRECTORY_SEPARATOR . $routeFile;
			$file = new UploadedFile($filepath, $routeFile);
			$s3->saveRouteFile($file, $route);
		}
		return 0;
	}

	/**
	 * Gets the route files from the passed in local directory
	 *
	 * @param string $dir
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getRouteFiles(string $dir)
	{
		if(!is_dir($dir))
		{
			throw new \Exception("Unable to read directory $dir");
		}
		$files = array_diff(scandir($dir), ['..', '.']);
		if(empty($files))
		{
			throw new \Exception("No files found in $dir");
		}
		$routeFiles = [];
		foreach($files as $file)
		{
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			if(in_array($ext, ['kmz', 'kml']))
			{
				$routeFiles[] = $file;
			}
		}
		if(empty($routeFiles))
		{
			throw new \Exception("No route files found in $dir");
		}

		return $routeFiles;
	}

	/**
	 * Gets a mapping of the passed in route files to the route file urls found in the route_archive table
	 *
	 * @param int   $zoneId
	 * @param array $routeFiles
	 *
	 * @return array
	 */
	public function getRouteUrlMap(int $zoneId, array $routeFiles)
	{
		$manager = new RouteManager($this->container->dbConn);
		$zoneRouteFiles = [];
		foreach($routeFiles as $routeFile)
		{
			$zoneRouteFiles["$zoneId-$routeFile"] = $routeFile;
		}

		$qb = $this->container->dbConn->createQueryBuilder();
		$qb->select([
			'route_id',
			'route_file_url',
			'route_name'
		])
			->from('route_archive')
			->where($qb->expr()->in('route_name', [':route_names']))
			->setParameter(':route_names', array_keys($zoneRouteFiles), Connection::PARAM_STR_ARRAY);

		$rows = $qb->execute()->fetchAll();
		if(empty($rows))
		{
			return [];
		}
		$map = [];
		foreach($rows as $row)
		{
			$map[$zoneRouteFiles[$row['route_name']]] = $manager->getRouteInfo((int)$row['route_id']);
		}

		return $map;
	}

	/**
	 * @return S3ObjectStore
	 */
	protected function getS3ObjectStorage()
	{
		$wrapper = new S3Helper(AWSConfig::getStandardConfig());
		/** @var S3Client $s3 */
		$s3 = $wrapper->getClient();
		return new S3ObjectStore($s3, Env::get(Env::TOE_S3_ROUTE_BUCKET));
	}
}