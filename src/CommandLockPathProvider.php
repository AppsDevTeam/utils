<?php
namespace ADT\Utils;

class CommandLockPathProvider {

	private $lockPath;

	/**
	 * CommandLockPathProvider constructor.
	 * @param string $path Directory with locks
	 * @param string $format Should only be file name, any instances of `$cmd$` are replaced with $commandName
	 *		in subsequent getPath and getFolder calls
	 * @throws \Exception
	 */
	public function __construct(string $path, string $format = '$cmd$.lock') {
		$this->createPathString($path, $format);
	}

	protected function createPathString($path, $format) {
		if ($path[strlen($path) - 1] !== '/' && $path[strlen($path) - 1] !== '\\') {
			$path .= '/';
		}
		if (strpos($path, '$cmd$') !== false) {
			throw new \Exception("Path can't include \$cmd\$");
		}
		if (strpos($format, '$cmd$') === false) {
			$this->lockPath = $path . '.$cmd$.lock';
		}
		else {
			$this->lockPath = $path . $format;
		}
	}

	public function getPath(string $commandName = '') {
		$commandName = preg_replace('/[^-a-zA-Z0-9]/', '-', $commandName);
		return str_replace('$cmd$', $commandName, $this->lockPath);
	}

	public function getFolder(string $commandName = '') {
		$path = $this->getPath($commandName);
		return substr($path, 0, strripos($path, '/') + 1);
	}

}