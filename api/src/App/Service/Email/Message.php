<?php
declare(strict_types=1);


namespace TOE\App\Service\Email;


class Message
{
	private $to;
	private $from;
	private $fromName;
	private $cc;
	private $bcc;
	private $subject;
	private $body;

	private $sent;
	private $messageId;

	public function __construct()
	{
		$this->sent = false;
	}

	public function recordMessageId($id)
	{
		$this->sent = true;
		$this->messageId = $id;
	}

	public function setTo($to)
	{
		$this->to = $to;
		return $this;
	}

	public function getTo()
	{
		return $this->to;
	}

	public function setFrom($from)
	{
		$this->from = $from;
		return $this;
	}

	public function setFromName($fromName)
	{
		$this->fromName = $fromName;
		return $this;
	}

	public function setCC($cc)
	{
		$this->cc = $cc;
		return $this;
	}

	public function setBCC($bcc)
	{
		$this->bcc = $bcc;
		return $this;
	}

	public function setSubject($subject)
	{
		$this->subject = $subject;
		return $this;
	}

	public function setBody($body)
	{
		$this->body = $body;
		return $this;
	}

	public function getFrom()
	{
		return $this->from;
	}

	public function getFromName()
	{
		return $this->fromName;
	}

	public function getBody()
	{
		return $this->body;
	}

	public function getSubject()
	{
		return $this->subject;
	}
}