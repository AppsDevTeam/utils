<?php

namespace ADT\Utils;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

class Utils
{
	final static public function realEmpty($value): bool
	{
		return empty($value) && $value !== 0 && $value !== '0';
	}

	final public static function getDateTimeFromArray(mixed $data, bool $returnOriginalOnError = false)
	{
		if (!isset($data['date'], $data['timezone'], $data['timezone_type'])) {
			return $returnOriginalOnError ? $data : null;
		}

		if (!is_string($data['date']) || !is_string($data['timezone']) || !is_int($data['timezone_type'])) {
			return $returnOriginalOnError ? $data : null;
		}

		try {
			return new DateTimeImmutable($data['date'], new DateTimeZone($data['timezone']));
		} catch (Exception) {
			return $returnOriginalOnError ? $data : null;
		}
	}
}
