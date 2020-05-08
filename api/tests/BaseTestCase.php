<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: LENOVO-T430
 * Date: 11/11/2016
 * Time: 11:09 AM
 */

namespace TOETests;

use Doctrine\DBAL\Connection;
use Exception;
use PHPUnit\Framework\TestCase;
use Silex\Application;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use TOE\GlobalCode\Constants;
use TOE\GlobalCode\HTTPCodes;

class BaseTestCase extends TestCase
{
	#region Silex WebTestCase
	/**
	 * HttpKernelInterface instance.
	 *
	 * @var HttpKernelInterface
	 */
	protected $app;

	/**
	 * PHPUnit setUp for setting up the application.
	 *
	 * Note: Child classes that define a setUp method must call
	 * parent::setUp().
	 */
	protected function setUp(): void
	{
		$this->app = $this->createApplication();
	}


	/**
	 * Creates a Client.
	 *
	 * @param array $server Server parameters
	 *
	 * @return Client A Client instance
	 */
	public function createClient(array $server = [])
	{
		if (!class_exists('Symfony\Component\BrowserKit\Client')) {
			throw new \LogicException('Component "symfony/browser-kit" is required by WebTestCase.'.PHP_EOL.'Run composer require symfony/browser-kit');
		}

		return new Client($this->app, $server);
	}
	#endregion

	/* @var Client $client */
	protected $client;

	/* @var Response $lastResponse */
	protected $lastResponse;

	/* @var Connection $dbConn */
	protected $dbConn;

	private $loggedIn;

	private $loggedInEmail;

	private $loggedInPassword;

	/**
	 * @var int
	 */
	private $loggedInUserId;

	private $token;

	private $templatePostObj;

	public function createApplication()
	{
		$app = new Application();

		require __DIR__ . "/../src/app.php";
		require __DIR__ . "/../config/config.php";
		require __DIR__ . "/../config/routes.php";

		$app['debug'] = true;

		return $app;
	}

	public function getApplicationDir()
	{
		return $_SERVER['APP_DIR'];
	}

	/**
	 * @param string $email    The email of the user being used for testing. Must be a constant in clsTesterCreds and must be a key in the CREDS array
	 *
	 * @return bool true if login was successful and the client is now configured to be that user, false otherwise.
	 * @throws \Exception
	 */
	public function login($email)
	{
		return $this->loginAdhoc($email, clsTesterCreds::CREDS[$email]);
	}

	public function loginAdhoc($email, $password)
	{
		$this->setClient();

		if ($this->loggedInEmail === $email && password_verify($this->loggedInPassword, $password))
		{
			return false;
		}

		if ($this->loggedIn)
		{
			$this->signout();
		}

		$requestParams = [
			"Content-Type" => "application/json"
		];

		$loginObj = [
			"email"    => $email,
			"password" => $password
		];

		$serverData = [
			"CONTENT_TYPE" => "application/json"
		];

		$this->client->request('POST', '/login', $requestParams, [], $serverData, json_encode($loginObj));

		try
		{
			$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);
		}
		catch (Exception $ex)
		{
			$this->loggedIn = false;
			throw($ex);
		}

		$content = json_decode($this->lastResponse->getContent());

		self::assertTrue($content->success, "Response did not return a success value of true");

		//Set the login token in the client
		$this->token = $content->token;
		$cookie = new Cookie("authToken", $this->token->jwt);
		$this->client->getCookieJar()->set($cookie);
		$this->client->setServerParameter("HTTP_X-Bearer-Token", $this->token->jwt);

		//verify the token worked
		$this->client->request('GET', '/user/userInfo');
		$this->basicResponseCheck(HTTPCodes::SUCCESS_DATA_RETRIEVED);
		$content = json_decode($this->lastResponse->getContent());

		self::assertNotNull($content, "could not json_decode the userInfo returned from the server.");
		self::assertEquals(strtolower($email), strtolower($content->email), "Did not login as the intended email.");

		//set the login data
		$this->loggedIn = true;
		$this->loggedInEmail = $email;
		$this->loggedInPassword = $password;
		$this->loggedInUserId = (int)$content->id;

		return true;
	}

	public function signout()
	{
		if ($this->loggedIn === false)
		{
			return false;
		}

		$this->client->getCookieJar()->clear();
		$this->client->setServerParameter("HTTP_X-Bearer-Token", "");
		$this->loggedInEmail = false;
		$this->loggedInPassword = false;
		$this->loggedIn = false;

		return true;
	}

	public function loginAsAdmin()
	{
		$this->login(clsTesterCreds::SUPER_ADMIN_EMAIL);
	}

	/**
	 * For use in debugging tests. Echos the last response returned by the client. Returns true if there was a last response
	 *
	 * @return bool
	 */
	public function echoLastResponse()
	{
		if ($this->client === null || $this->client->getResponse() === null)
		{
			echo "\nNo client was set or there was no response in the client\n";

			return false;
		}

		echo "\n\nLast Response:\n" . $this->client->getResponse() . "\n\n";

		return true;
	}

	public function getLoggedIn()
	{
		return $this->loggedIn;
	}

	public function getLoggedInUserId()
	{
		return $this->loggedInUserId;
	}

	public function getLoggedInPassword()
	{
		return $this->loggedInPassword;
	}

	protected function getModifiedJSONObject($modifications = [])
	{
		if (($temp = $this->templatePostObj) === null)
		{
			return null;
		}


		foreach ($modifications as $key => $value)
		{
			if (!isset($temp[$key]))
			{
				throw new Exception("$key does not exist in the post object. Post Object: " . print_r($temp, true));
			}
			$temp[$key] = $value;
		}

		return $temp;
	}

	protected function loadJSONObject($filePath)
	{
		if (!is_readable($filePath))
		{
			if (file_exists($filePath))
			{
				echo "Unable to open file: $filePath\n";
			}
			else
			{
				echo "file $filePath does not exist.\n";
			}
			$this->templatePostObj = null;

			return;
		}

		try
		{
			$this->templatePostObj = json_decode(file_get_contents($filePath), true);
		}
		catch (Exception $e)
		{
			echo "Unable to get json object: " . $e->getMessage();
			$this->templatePostObj = null;
		}
	}

	protected function setClient()
	{
		if ($this->client === null)
		{
			$this->client = $this->createClient();
		}
	}

	protected function setDatabaseConnection()
	{
		if ($this->dbConn === null && $this->app !== null && isset($this->app['db']))
		{
			$this->dbConn = $this->app['db'];
		}
	}

	protected function initializeTest($login)
	{
		$this->setClient();
		$this->setDatabaseConnection();
		$this->login($login);
	}

	protected function basicResponseCheck($expectedCode)
	{
		$this->lastResponse = $this->client->getResponse();
		$message = "";
		if ($this->lastResponse->getStatusCode() != $expectedCode)
		{
			$content = json_decode($this->lastResponse->getContent());
			if (isset($content->message))
			{
				$message = $content->message;
			}
			else
			{
				echo $this->lastResponse->getContent();
			}
			if (isset($content->more))
			{
				echo "\nmore: " . $content->more . "\n";
			}
		}
		self::assertEquals($expectedCode, $this->lastResponse->getStatusCode(), "Response did not contain the expected status code. Message: $message");
	}

	protected function checkPOSTResponse($expectedCode, $POSTobj = false)
	{
		$this->lastResponse = $this->client->getResponse();
		if ($this->lastResponse->getStatusCode() !== $expectedCode && $POSTobj !== false)
		{
			echo "\n" . print_r($POSTobj, true) . "\n";
		}
		self::assertEquals($expectedCode, $this->lastResponse->getStatusCode(), "Response did not contain the expected status code");
	}

	protected function createThrowawayUser()
	{
		$this->setDatabaseConnection();
		$qb = $this->dbConn->createQueryBuilder();

		$email = clsTestHelpers::GetThrowAwayEmail(1);

		$qb->insert('user')
			->values([
				'email'       => ':email',
				'password'    => ':password',
				'first_name'  => ':first_name',
				'last_name'   => ':last_name',
				'date_joined' => 'NOW()',
				'region_id'   => 1,
				'hearing'     => ':hearing',
				'visual'      => ':visual',
				'mobility'    => ':mobility'
			])
			->setParameter(':email', $email, Constants::SILEX_PARAM_STRING)
			->setParameter(':password', password_hash('password', PASSWORD_DEFAULT), Constants::SILEX_PARAM_STRING)
			->setParameter(':first_name', 'throwaway', Constants::SILEX_PARAM_STRING)
			->setParameter(':last_name', 'account', Constants::SILEX_PARAM_STRING)
			->setParameter(':hearing', 'false', Constants::SILEX_PARAM_STRING)
			->setParameter(':visual', 'false', Constants::SILEX_PARAM_STRING)
			->setParameter(':mobility', 'false', Constants::SILEX_PARAM_STRING);

		$qb->execute();
		$qb = $this->dbConn->createQueryBuilder();

		$qb->select(
				'user_id',
				'email',
				'first_name',
				'last_name',
				'region_id'
			)
			->from('user')
			->where('email = :email')
			->setParameter(':email', $email, Constants::SILEX_PARAM_STRING);

		$result = $qb->execute()->fetch();
		$result['password'] = clsTesterCreds::NORMAL_USER_PASSWORD;

		return $result;
	}

	protected function removeUser($email)
	{
		$this->setDatabaseConnection();
		$qb = $this->dbConn->createQueryBuilder();
		$qb->delete('user');
		$qb->where("email = :email");
		$qb->setParameter('email', $email, Constants::SILEX_PARAM_STRING);
		$qb->execute();
	}

}