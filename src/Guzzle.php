<?php
namespace ADT\Utils;

class Guzzle {

	/**
	 * Returns TRUE if everything is allright, FALSE if it's repetable error, otherwise throws exception
	 *
	 * \GuzzleHttp\Exception\GuzzleException $guzzleException
	 * @return boolean|\GuzzleHttp\Exception\GuzzleException
	 */
	public static function handleError(\GuzzleHttp\Exception\GuzzleException $guzzleException) {

		if ($guzzleException instanceof \GuzzleHttp\Exception\ConnectException) {
			// HTTP Code 0
			// On Sparkpost or ADT MailApi the request was successfuly processed even if there is no response
			// Let's believe it is common behaviour
			return TRUE;
		}

		if ($guzzleException instanceof \GuzzleHttp\Exception\ServerException) {
			// HTTP Code 5xx
			return FALSE;
		}

		// other exceptions like 3xx (TooManyRedirectsException) or 4xx (\GuzzleHttp\Exception\ClientException) are unrepeatable and we want to throw exception
		throw $guzzleException;
	}

}
