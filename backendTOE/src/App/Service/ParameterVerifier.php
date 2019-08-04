<?php

namespace TOE\App\Service;
use Symfony\Component\HttpFoundation\Request;

class ParameterVerifier
{
	private $template;

	private $templates;

	public function __construct($templates)
	{
		$this->templates = $templates;
	}

	public function verify(Request $request)
	{
		$route = $request->get('_route');
		$route = str_replace(['GET_', 'POST_', 'PUT_', 'DELETE_'], "", $route);
		$route = str_replace('_', "/", $route);

		$result = [];
		if (isset($this->templates[$route]))
		{
			$this->template = $this->templates[$route];

			foreach ($this->template as $key => $type)
			{
				$result[$key] = $request->request->get($key);
				if ($result[$key] === null)
				{
					return [
						'success' => false,
						'message' => "$key is NULL or empty: " . $result[$key]
					];
				};
				if (gettype($result[$key]) !== $type)
				{
					return [
						'success' => false,
						'message' => "$key is " . gettype($result[$key]) . "; need $type."
					];
				};
			};
		}

		return $result;
	}
}

?>
