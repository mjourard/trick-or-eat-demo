<?php
declare(strict_types=1);


namespace TOE\App\Service\Email;


use Aws\Exception\AwsException;
use Aws\Ses\SesClient;

class AwsSesClient extends aClient
{
	/**
	 * @var SesClient
	 */
	private $ses;

	public function __construct($configArgs)
	{
		$args = [
			'version' => '2010-12-01',
			'region'  => 'us-east-1'
		];
		if (!empty($configArgs['region']))
		{
			$args['region'] = $configArgs['region'];
		}
		if (!empty($configArgs['RoleArn']))
		{
			$args['profile'] = 'default';
			$args['RoleArn'] = $configArgs['RoleArn'];
		}
		if (!empty($configArgs['key']) && !empty($configArgs['secret']))
		{
			$args['credentials'] = [
				'key' => $configArgs['key'],
				'secret' => $configArgs['secret']
			];
		}
		$this->ses = new SesClient($args);
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
			$result = $this->ses->sendEmail([
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
			$msg->RecordMessageId($result['MessageId']);
		} catch (AwsException $e) {
			// output error message if fails
			throw new EmailException("The email was not sent. Error message: ".$e->getAwsErrorMessage(), $e);
		}
	}
}