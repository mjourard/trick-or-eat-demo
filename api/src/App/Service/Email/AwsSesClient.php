<?php
declare(strict_types=1);


namespace TOE\App\Service\Email;


use Aws\Exception\AwsException;
use Aws\Ses\SesClient;
use TOE\App\Service\AWS\aWrapper;

class AwsSesClient extends aWrapper implements iClient
{
	/**
	 * @var SesClient
	 */
	protected $client;

	public function __construct(array $configArgs)
	{
		$configArgs['version'] = '2010-12-01';
		$configArgs['region'] = 'us-east-1';
		parent::__construct($configArgs);
	}

	/**
	 * @param Message $msg
	 *
	 * @return void
	 * @throws EmailException
	 */
	public function sendEmail(Message $msg)
	{
		$to = $msg->getTo();
		if (!is_array($to))
		{
			$to = [$to];
		}

		try {
			$result = $this->client->sendEmail([
				'Destination' => [
					'ToAddresses' => $to,
				],
				'ReplyToAddresses' => [$msg->getFrom()],
				'Source' => $msg->getFrom(),
				'Message' => [
					'Body' => [
						'Html' => [
							'Charset' => 'UTF-8',
							'Data' => $msg->getBody(),
						],
						'Text' => [
							'Charset' => 'UTF-8',
							'Data' => $msg->getBody(),
						],
					],
					'Subject' => [
						'Charset' => 'UTF-8',
						'Data' => $msg->getSubject(),
					],
				],
			]);
			$msg->recordMessageId($result['MessageId']);
		} catch (AwsException $e) {
			// output error message if fails
			throw new EmailException("The email was not sent. Error message: ".$e->getAwsErrorMessage(), $e);
		}
	}

	public function getClientClass()
	{
		return SesClient::class;
	}
}