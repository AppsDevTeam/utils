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
	private const MAX_BODY_LENGTH = 10000;

	/**
	 * @throws Throwable
	 */
	public static function handleException(Throwable $e): ?Exception
	{
		if ($e instanceof GuzzleException) {
			$message = '';
			if ($e instanceof ConnectException || $e instanceof RequestException) {
				$message = "--- REQUEST ---\n" . self::sanitizeMessage(Message::toString($e->getRequest())) . "\n --- RESPONSE ---\n";
			}
			$message .= ($e instanceof RequestException && $e->getResponse() ? self::sanitizeMessage(Message::toString($e->getResponse())) : $e->getMessage());

			throw new Exception($message);
		}

		throw $e;
	}

	private static function sanitizeMessage(string $message): string
	{
		// Odstraneni binarnich dat (null byty apod.)
		if (preg_match('/[^\x20-\x7E\x0A\x0D\t]/u', $message)) {
			// Najdeme konec hlavicek (prazdny radek)
			$headerEnd = strpos($message, "\r\n\r\n");
			if ($headerEnd === false) {
				$headerEnd = strpos($message, "\n\n");
			}

			if ($headerEnd !== false) {
				$headers = substr($message, 0, $headerEnd);
				return $headers . "\n\n[binary data removed]";
			}

			return '[binary data removed]';
		}

		// Oriznuti prilis dlouhych textovych odpovedi
		if (strlen($message) > self::MAX_BODY_LENGTH) {
			return substr($message, 0, self::MAX_BODY_LENGTH) . "\n\n... [truncated, total " . strlen($message) . " bytes]";
		}

		return $message;
	}
}
