<?php


namespace TOE\App\Service\Email;


abstract class aClient
{
	/**
	 * @param Message $msg
	 *
	 * @return void
	 * @throws EmailException
	 */
	public abstract function sendEmail(Message $msg);
}