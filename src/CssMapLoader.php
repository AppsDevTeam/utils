<?php
namespace ADT\Utils;


class CssMapLoader {
	/**
	 * Looks for $moduleName.module.scss.json in $dir path and returns
	 * content as array on success
	 *
	 * @param string $dir
	 * @param string|null $moduleName
	 * @return array
	 */
	public static function fromWebpack($dir, $moduleName = 'index') {
		$filePath = $dir . "/$moduleName.module.scss.json";

		if (! file_exists($filePath)) {
			throw new \InvalidArgumentException("Css module file not found in ". $filePath);
		}

		$file = file_get_contents($filePath);

		if ($file === false) {
			throw new \InvalidArgumentException('Css Module file not found in ' . $filePath);
		}

		$decodedModule = json_decode($file, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new \UnexpectedValueException(
				'Css Module file is not valid json file: ' . json_last_error_msg()
			);
		}

		return $decodedModule;
	}
}
