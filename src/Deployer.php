<?php

namespace ADT\Utils;

class Deployer
{
	/**
	 * Get current release number.
	 */
	public static function getReleaseNumber(): int
	{
		// Example of __DIR__: '/var/www/my-project.com/releases/7/private/vendor/...'
		if (preg_match_all('|releases/(\d+)/|', realpath(__DIR__), $m, PREG_SET_ORDER) > 0) {
			return (int)(end($m)[1]);
		}
		return 0;
	}
}
