<?php

namespace ADT\Utils;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

class Utils
{
	final public static function getDateTimeFromArray(array|string $data): ?DateTimeImmutable
	{
		if (!isset($data['date'], $data['timezone'], $data['timezone_type'])) {
			return null;
		}

		if (!is_string($data['date']) || !is_string($data['timezone']) || !is_int($data['timezone_type'])) {
			return null;
		}

		try {
			return new DateTimeImmutable($data['date'], new DateTimeZone($data['timezone']));
		} catch (Exception) {
			return null;
		}
	}
}