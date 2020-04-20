<?php

namespace ADT\Utils;

trait CssModule 
{
	/**
	 * Automatically adds className map to template on attached
	 */
	public function injectCssModule() 
	{
		$this->onAfterAttached[] = function() {
			$this->template->className = $this->load();
		};
	}

	/**
	 * Looks for $moduleName.module.scss.json in current directory and returns
	 * content as array on success
	 * @param string $moduleName
	 * @param string|null $dir
	 * @return array
	 */
	private function load(string $moduleName = 'index', string $dir = NULL): array 
	{
		$dir = $dir ?? dirname($this->getReflection()->getFileName());
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
