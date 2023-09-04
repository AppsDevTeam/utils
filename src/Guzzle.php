<?php
namespace ADT\Utils;

use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Message;
use Throwable;

class Guzzle
{
	/**
	 * @throws Throwable
	 */
	public static function handleException(Throwable $e): ?Exception
	{
		if ($e instanceof GuzzleException) {
			$message = '';
			if ($e instanceof ConnectException || $e instanceof RequestException) {
				$message = "--- REQUEST ---\n" . Message::toString($e->getRequest()) . "\n --- RESPONSE ---\n";
			}
			$message .= ($e instanceof RequestException ? Message::toString($e->getResponse()) : $e->getMessage());

			throw new Exception($message);
		}

		throw $e;
	}
}
