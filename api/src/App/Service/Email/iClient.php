<?php
declare(strict_types=1);


namespace TOE\App\Service\Email;


interface iClient
{
	/**
	 * @param Message $msg
	 *
	 * @return void
	 * @throws EmailException
	 */
	public function sendEmail(Message $msg);
}