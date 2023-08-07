<?php

declare(strict_types=1);

namespace ADT\Utils;

use Exception;

class FileSystem
{
	/**
	 * @throws Exception
	 */
	public static function createDirAtomically(string $dir): bool
	{
		error_clear_last();
		@mkdir($dir, 0770, true);
		if ($lastError = error_get_last()) {
			if (!is_dir($dir)) {
				throw new Exception($lastError['message']);
			} else {
				return false;
			}
		}

		return true;
	}
}