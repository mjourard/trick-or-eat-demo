<?php
declare(strict_types=1);


namespace TOE\App\Service\Email;


abstract class aClient
{
	/**
	 * @param Message $msg
	 *
	 * @return void
	 * @throws EmailException
	 */
	abstract public function sendEmail(Message $msg);
}