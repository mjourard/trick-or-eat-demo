<?php
declare(strict_types=1);


namespace TOE\App\Service\Route\Archive;


use Symfony\Component\HttpFoundation\File\UploadedFile;

class Route
{
	private $routeId;
	/**
	 * @var string the value saved in the database under route_file_url, but it's just the path
	 */
	public $routeFilePath;
	public $routeName;
	public $requiredPeople;
	public $type;
	public $wheelchairAccessible;
	public $blindAccessible;
	public $hearingAccessible;
	public $zoneId;
	public $ownerUserId;
	private $filename;

	/**
	 * Initializes a new Route object that does not exist in the system yet and was just uploaded
	 *
	 * @param UploadedFile $file
	 * @param              $zoneId
	 * @param              $ownerUserId
	 *
	 * @return Route
	 */
	public static function init(UploadedFile $file, $zoneId, $ownerUserId)
	{
		$obj = new self([]);
		$obj->zoneId = $zoneId;
		$obj->ownerUserId = $ownerUserId;
		$obj->filename = $file->getClientOriginalName();
		$obj->routeName = self::getRouteName($zoneId, $file->getClientOriginalName());
		$obj->routeFilePath = self::getRouteHostingUrlPath($zoneId, $file->getClientOriginalName());
		return $obj;
	}

	public function __construct(array $dbRow)
	{
		$map = [
			'route_id'              => 'routeId',
			'route_file_url'        => 'routeFilePath',
			'route_name'            => 'routeName',
			'required_people'       => 'requiredPeople',
			'type'                  => 'type',
			'wheelchair_accessible' => 'wheelchairAccessible',
			'blind_accessible'      => 'blindAccessible',
			'hearing_accessible'    => 'hearingAccessible',
			'zone_id'               => 'zoneId',
			'owner_user_id'         => 'ownerUserId',
		];
		foreach($map as $dbKey => $classProp)
		{
			if(isset($dbRow[$dbKey]))
			{
				$this->{$classProp} = $dbRow[$dbKey];
			}
		}
	}

	public static function getRouteName($zoneId, $fileName)
	{
		return "$zoneId-" . str_replace(" ", "_", $fileName);
	}

	public static function getRouteHostingUrlPath($zoneId, $fileName)
	{
		$ext = "";
		$info = pathinfo($fileName);
		if(!empty($info) && isset($info['extension']))
		{
			$ext = $info['extension'];
		}

		return uniqid("/$zoneId-") . ".$ext";
	}

	/**
	 * sets the route id
	 *
	 * @param int|string $routeId
	 */
	public function setRouteId($routeId)
	{
		$this->routeId = (int)$routeId;
	}

	/**
	 * Gets the route id
	 *
	 * @return int
	 */
	public function getRouteId()
	{
		if ($this->routeId === null)
		{
			return -1;
		}
		return (int)$this->routeId;
	}

	/**
	 * Resets the object when the route file was deleted
	 */
	public function fileWasDeleted()
	{
		$this->routeFilePath = null;
		$this->filename = null;
	}

	/**
	 * Determines if the passed in Route object has a linked file or not
	 *
	 * @return bool
	 */
	public function hasFile()
	{
		return !empty($this->routeFilePath);
	}
}