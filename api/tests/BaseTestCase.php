<?php
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
use TOE\GlobalCode\clsConstants;
use TOE\GlobalCode\clsHTTPCodes;

require __DIR__ . '/bootstrap.php';

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

	public function CreateApplication()
	{
		$app = new Application();

		require __DIR__ . "/../src/app.php";
		require __DIR__ . "/../config/config.php";
		require __DIR__ . "/../config/routes.php";

		$app['debug'] = true;

		return $app;
	}

	public function GetApplicationDir()
	{
		return $_SERVER['APP_DIR'];
	}

	/**
	 * @param string $email    The email of the user being used for testing. Must be a constant in clsTesterCreds and must be a key in the CREDS array
	 *
	 * @return bool true if login was successful and the client is now configured to be that user, false otherwise.
	 * @throws \Exception
	 */
	public function Login($email)
	{
		return $this->LoginAdhoc($email, clsTesterCreds::CREDS[$email]);
	}

	public function LoginAdhoc($email, $password)
	{
		$this->SetClient();

		if ($this->loggedInEmail === $email && password_verify($this->loggedInPassword, $password))
		{
			return false;
		}

		if ($this->loggedIn)
		{
			$this->Signout();
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
			$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
		}
		catch (Exception $ex)
		{
			$this->loggedIn = false;
			throw($ex);
		}

		$content = json_decode($this->lastResponse->getContent());

		$this->assertTrue($content->success, "Response did not return a success value of true");

		//Set the login token in the client
		$this->token = $content->token;
		$cookie = new Cookie("authToken", $this->token->jwt);
		$this->client->getCookieJar()->set($cookie);
		$this->client->setServerParameter("HTTP_X-Bearer-Token", $this->token->jwt);

		//verify the token worked
		$this->client->request('GET', '/user/userInfo');
		$this->BasicResponseCheck(clsHTTPCodes::SUCCESS_DATA_RETRIEVED);
		$content = json_decode($this->lastResponse->getContent());

		$this->assertNotNull($content, "could not json_decode the userInfo returned from the server.");
		$this->assertEquals(strtolower($email), strtolower($content->email), "Did not login as the intended email.");

		//set the login data
		$this->loggedIn = true;
		$this->loggedInEmail = $email;
		$this->loggedInPassword = $password;
		$this->loggedInUserId = (int)$content->id;

		return true;
	}

	public function Signout()
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

	public function LoginAsAdmin()
	{
		$this->Login(clsTesterCreds::SUPER_ADMIN_EMAIL);
	}

	/**
	 * For use in debugging tests. Echos the last response returned by the client. Returns true if there was a last response
	 *
	 * @return bool
	 */
	public function EchoLastResponse()
	{
		if ($this->client === null || $this->client->getResponse() === null)
		{
			echo "\nNo client was set or there was no response in the client\n";

			return false;
		}

		echo "\n\nLast Response:\n" . $this->client->getResponse() . "\n\n";

		return true;
	}

	public function GetLoggedIn()
	{
		return $this->loggedIn;
	}

	public function GetLoggedInUserId()
	{
		return $this->loggedInUserId;
	}

	public function GetLoggedInPassword()
	{
		return $this->loggedInPassword;
	}

	protected function GetModifiedJSONObject($modifications = [])
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

	protected function LoadJSONObject($filePath)
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

	protected function SetClient()
	{
		if ($this->client === null)
		{
			$this->client = $this->createClient();
		}
	}

	protected function SetDatabaseConnection()
	{
		if ($this->dbConn === null && $this->app !== null && isset($this->app['db']))
		{
			$this->dbConn = $this->app['db'];
		}
	}

	protected function InitializeTest($login)
	{
		$this->SetClient();
		$this->SetDatabaseConnection();
		$this->Login($login);
	}

	protected function BasicResponseCheck($expectedCode)
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
		$this->assertEquals($expectedCode, $this->lastResponse->getStatusCode(), "Response did not contain the expected status code. Message: $message");
	}

	protected function POSTResponseCheck($expectedCode, $POSTobj = false)
	{
		$this->lastResponse = $this->client->getResponse();
		if ($this->lastResponse->getStatusCode() !== $expectedCode && $POSTobj !== false)
		{
			echo "\n" . print_r($POSTobj, true) . "\n";
		}
		$this->assertEquals($expectedCode, $this->lastResponse->getStatusCode(), "Response did not contain the expected status code");
	}

	protected function CreateThrowawayUser()
	{
		$this->SetDatabaseConnection();
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
			->setParameter(':email', $email, clsConstants::SILEX_PARAM_STRING)
			->setParameter(':password', '$2y$10$SZ7H6yhS4JGTWWY6SskuxO4dyG6R3c5is2GVDJWvIIQEGaKPM4/X.', clsConstants::SILEX_PARAM_STRING)
			->setParameter(':first_name', 'throwaway', clsConstants::SILEX_PARAM_STRING)
			->setParameter(':last_name', 'account', clsConstants::SILEX_PARAM_STRING)
			->setParameter(':hearing', 'false', clsConstants::SILEX_PARAM_STRING)
			->setParameter(':visual', 'false', clsConstants::SILEX_PARAM_STRING)
			->setParameter(':mobility', 'false', clsConstants::SILEX_PARAM_STRING);

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
			->setParameter(':email', $email, clsConstants::SILEX_PARAM_STRING);

		$result = $qb->execute()->fetch();
		$result['password'] = clsTesterCreds::NORMAL_USER_PASSWORD;

		return $result;
	}

	protected function RemoveUser($email)
	{
		$this->SetDatabaseConnection();
		$qb = $this->dbConn->createQueryBuilder();
		$qb->delete('user');
		$qb->where("email = :email");
		$qb->setParameter('email', $email, clsConstants::SILEX_PARAM_STRING);
		$qb->execute();
	}

}