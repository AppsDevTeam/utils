<?php

namespace ADT\Utils;

use Exception;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Message;
use ADT\BackgroundQueue\Exception\TemporaryErrorException;
use Closure;

class BackgroundQueue
{
	/**
	 * @throws BadRequestException
	 * @throws TemporaryErrorException
	 */
	public static function handleException(Exception $e): void
	{
		if ($e instanceof GuzzleException) {
			$message = Message::toString($e->getRequest()) . ($e instanceof BadResponseException ? Message::toString($e->getResponse()) : $e->getMessage());

			if ($e instanceof ServerException || $e instanceof ConnectException) {
				throw new TemporaryErrorException($message);
			}

			throw new BadRequestException($message);
		}

		throw $e;
	}

	/**
	 * @throws BadRequestException
	 * @throws TemporaryErrorException
	 */
	public static function handleGuzzleResult(Closure $closure)
	{
		try {
			return $closure();
		} catch (Exception $e) {
			self::handleException($e);
		}
	}
}