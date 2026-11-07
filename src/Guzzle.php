<?php
namespace ADT\Utils;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
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
			// Guzzle 7 má request u ConnectException a RequestException a odpověď (nepovinně)
			// u RequestException; Guzzle 8 má request u každé TransferException a odpověď jen
			// u ResponseException - RequestException::getResponse() už neexistuje
			$request = method_exists($e, 'getRequest') ? $e->getRequest() : null;
			$response = method_exists($e, 'getResponse') ? $e->getResponse() : null;

			$message = '';
			if ($request instanceof RequestInterface) {
				$message = "--- REQUEST ---\n" . self::sanitizeMessage(self::messageToString($request)) . "\n --- RESPONSE ---\n";
			}
			$message .= ($response instanceof ResponseInterface ? self::sanitizeMessage(self::messageToString($response)) : $e->getMessage());

			throw new Exception($message);
		}

		throw $e;
	}

	/**
	 * Obdoba GuzzleHttp\Psr7\Message::toString(), ale tělo čte ze streamu jen do MAX_BODY_LENGTH.
	 * Message::toString() načítá celé tělo do paměti, což u velkých těl (např. upload souboru
	 * v multipart requestu) shodí proces na memory limitu ještě před ořezáním v sanitizeMessage().
	 */
	private static function messageToString(MessageInterface $message): string
	{
		if ($message instanceof RequestInterface) {
			$msg = trim($message->getMethod() . ' ' . $message->getRequestTarget()) . ' HTTP/' . $message->getProtocolVersion();
			if (!$message->hasHeader('host')) {
				$msg .= "\r\nHost: " . $message->getUri()->getHost();
			}
		} elseif ($message instanceof ResponseInterface) {
			$msg = 'HTTP/' . $message->getProtocolVersion() . ' ' . $message->getStatusCode() . ' ' . $message->getReasonPhrase();
		} else {
			$msg = '';
		}

		foreach ($message->getHeaders() as $name => $values) {
			$msg .= "\r\n" . $name . ': ' . implode(', ', $values);
		}

		$body = '';
		$stream = $message->getBody();
		if ($stream->isSeekable() && $stream->isReadable()) {
			$size = $stream->getSize();
			$stream->seek(0);
			$body = $stream->read(self::MAX_BODY_LENGTH);
			if ($size === null || $size > self::MAX_BODY_LENGTH) {
				$body .= "\n\n... [truncated" . ($size !== null ? ', total ' . $size . ' bytes' : '') . ']';
			}
		}

		return $msg . "\r\n\r\n" . $body;
	}

	private static function sanitizeMessage(string $message): string
	{
		// Odstraneni binarnich dat (null byty apod.)
		// Pozor: bez modifikatoru "u" — s nim preg_match na nevalidnim UTF-8 (tzn. prave na
		// binarnich datech) vraci false misto 1 a binarka by prosla dal do zpravy vyjimky.
		// Ta pak konci treba v mail(), ktere na null bajtu spadne na ValueError.
		if (preg_match('/[^\x20-\x7E\x0A\x0D\t]/', $message)) {
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
