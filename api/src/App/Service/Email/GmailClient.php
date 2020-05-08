<?php
declare(strict_types=1);


namespace TOE\App\Service\Email;


use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PHPMailerOAuth;
use SMTP;

class GmailClient extends aClient
{
	/**
	 * @var PHPMailerOAuth
	 */
	private $mail;

	public function __construct($config)
	{
		$this->mail = new PHPMailerOAuth();
		// 1 = messages only
		// 2 = errors + messages
		// 3 = detailed errors + messages
		$this->mail->isSMTP();
		$this->mail->SMTPDebug = SMTP::DEBUG_OFF;
		if (!empty($config['smtp_debug']))
		{
			$this->mail->SMTPDebug = $config['smtp_debug'];
		}
		$values = [
			'oauthUserEmail'    => 'oauth_user_email',
			'oauthClientId'     => 'oauth_client_id',
			'oauthClientSecret' => 'oauth_client_secret',
			'oauthRefreshToken' => 'oauth_refresh_token',
		];
		foreach($values as $prop => $key)
		{
			if(empty($config[$key]))
			{
				throw new EmailException("Missing configuration key when trying to get Gmail Client: $key");
			}
			$this->mail->{$prop} = $config[$key];
		}

		$this->mail->SMTPOptions = [
			'ssl' => [
				'verify_peer'       => false,
				'verify_peer_name'  => false,
				'allow_self_signed' => true
			]
		];

		$this->mail->SMTPAuth = true;                  // enable SMTP authentication
		$this->mail->SMTPSecure = "tls";            //use tls protocol
		$this->mail->Host = "smtp.gmail.com";      // SMTP server
		$this->mail->Port = 587;                   // SMTP port
		$this->mail->AuthType = 'XOAUTH2';
	}

	/**
	 * Sends the passed in Message through gmail's SMTP service
	 *
	 * @param Message $msg
	 *
	 * @throws EmailException
	 */
	public function sendEmail(Message $msg)
	{
		try
		{
			$this->mail->setFrom($msg->getFrom(), $msg->getFromName());
			$this->mail->Subject = $msg->getSubject();
			$this->mail->msgHTML($msg->getBody());
			$this->mail->addAddress($msg->getTo());

			if($this->mail->send() === false)
			{
				throw new EmailException($this->mail->ErrorInfo);
			}
		}
		catch(IdentityProviderException $ex)
		{
			throw new EmailException("Reset Password Email Failed with " . gettype($ex) . ": " . $ex->getMessage(), $ex);
		}
		catch(\phpmailerException $ex)
		{
			throw new EmailException("Reset Password Email Failed with " . gettype($ex) . ": " . $ex->getMessage(), $ex);
		}
	}
}