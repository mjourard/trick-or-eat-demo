<?php
declare(strict_types=1);

namespace TOE\App\Service\Password;


use DateTime;
use Firebase\JWT\JWT;

class WebToken
{
	public const JWT_ALGORITHM = 'HS512';
	/**
	 * @var DateTime
	 */
	private $issuedAt;
	/**
	 * @var DateTime
	 */
	private $expiresAt;
	private $userId;
	private $uniqueId;
	public function __construct(DateTime $issuedAt, DateTime $expiresAt, $userId)
	{
		$this->issuedAt =  $issuedAt;
		$this->expiresAt = $expiresAt;
		$this->userId = $userId;
		$this->uniqueId = uniqid();
	}

	public function getData()
	{
		return [
			'iat'      => $this->issuedAt->getTimestamp(),
			'exp'      => $this->expiresAt->getTimestamp(),
			'userID'   => $this->userId,
			'uniqueID' => $this->uniqueId
		];
	}

	public function getUserId()
	{
		return $this->userId;
	}

	public function getIssuedAt()
	{
		return $this->issuedAt;
	}

	public function getExpiredAt()
	{
		return $this->expiresAt;
	}

	public function getUniqueId()
	{
		return $this->uniqueId;
	}

	public function encode($key)
	{
		return JWT::encode(
			$this->getData(),
			$key,
			self::JWT_ALGORITHM
		);
	}

	/**
	 * decodes a jwt string into the password reset token
	 *
	 * @param string $token
	 * @param string $key
	 *
	 * @return WebToken
	 * @throws PasswordResetException
	 */
	public static function decode($token, $key)
	{
		$data = JWT::decode($token, $key, [self::JWT_ALGORITHM]);
		$elements = [
			'iat',
			'exp',
			'userID',
			'uniqueID'
		];
		foreach($elements as $element)
		{
			if(!property_exists($data, $element))
			{
				throw new PasswordResetException("Decode error: token did not contain element '$element'");
			}
		}
		$iatDT = new DateTime('now', new \DateTimeZone('utc'));
		$iatDT->setTimestamp($data->iat);
		$expDT = clone $iatDT;
		$expDT->setTimestamp($data->exp);
		$jwt = new self($iatDT, $expDT, $data->userID);
		$jwt->setUniqueId($data->uniqueID);
		return $jwt;
	}

	private function setUniqueId($id)
	{
		$this->uniqueId = $id;
	}
}