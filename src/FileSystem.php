<?php

declare(strict_types=1);

namespace ADT\Utils;

use Nette\IOException;

class FileSystem
{
	/** umask to restore by the shutdown guard; null = guard not registered yet */
	private static ?int $originalUmask = null;

	/**
	 * Creates a directory including missing parents. Safe to call concurrently from multiple processes.
	 * Unlike plain mkdir(), $mode is applied exactly (umask is ignored), so the directory never exists
	 * with other permissions than requested.
	 *
	 * @return bool true if the directory was created by this call, false if it already existed
	 * @throws IOException if the directory cannot be created (e.g. insufficient permissions or a file with the same name exists)
	 */
	public static function createDirAtomically(string $dir, int $mode = 0770): bool
	{
		error_clear_last();
		$umask = umask(0);
		self::registerUmaskGuard($umask);
		try {
			$created = @mkdir($dir, $mode, true);
		} finally {
			umask($umask);
		}

		if ($created) {
			return true;
		}
		if (is_dir($dir)) {
			return false;
		}

		$error = error_get_last();
		throw new IOException(sprintf("Unable to create directory '%s'.%s", $dir, $error ? ' ' . $error['message'] : ''));
	}

	/**
	 * `finally` does not run on fatal errors (memory limit, max_execution_time). A PHP-FPM worker survives
	 * a fatal error and would serve subsequent requests with umask 0, so restore it in a shutdown function too.
	 * Registered once per process; the shutdown function always restores the most recently saved umask.
	 */
	private static function registerUmaskGuard(int $umask): void
	{
		$register = self::$originalUmask === null;
		self::$originalUmask = $umask;
		if ($register) {
			register_shutdown_function(static function (): void {
				umask(self::$originalUmask);
			});
		}
	}
}
