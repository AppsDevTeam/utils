<?php
namespace ADT\Utils;

Trait CommandLock {

	// Class using this trait must have a protected or public property commandLockPathProvider of type CommandLockPathProvider

	/**
	 *	=== Example config.neon for Nette ===
	 *	parameters:
	 *		commandLock:
	 *			path: %privateDir%/temp/locks
	 *			format: $cmd$.lock
	 *	services:
	 *		- \ADT\Utils\CommandLockPathProvider(%commandLock.path%, %commandLock.format%)
	 */

	/**
	 *	=== Example class body ===
	 *
	 *	use \ADT\Utils\CommandLock;
	 *	/** @var \ADT\Utils\CommandLockPathProvider * /
	 *	protected $commandLockPathProvider;
	 *
	 *	protected function initialize(InputInterface $input, OutputInterface $output) {
	 *		$containerHelper = $this->getHelper('container');
	 *		$this->commandLockPathProvider = $containerHelper->getByType(\ADT\Utils\CommandLockPathProvider::class);
	 *	}
	 *
	 *	protected function execute(InputInterface $input, OutputInterface $output) {
	 *		$this->tryLock();
	 *		// Execute command...
	 *		$this->tryUnlock();
	 *	}
	 */

	/**
	 * Tries to create a lock file with its process id.
	 * If file exists but process id in it doesn't belong to any running process, overrides the lock.
	 * If file exists and process id in it belongs to a running process, fails.
	 * @param bool $strict If true, failure throws an exception
	 * @param string $identifier If set, adds a "-$identifier" suffix to the name of the lock file.
	 * @return bool true in case of success, false otherwise
	 * @throws \Exception
	 */
	protected function tryLock(bool $strict = true, $identifier = null) {
		$folderName = $this->getFolder($identifier);
		if (!file_exists($folderName)) {
			mkdir($folderName, 0777, true);
		}
		$pathName = $this->getPath($identifier);
		$stream = fopen($pathName, 'a+');
		fseek($stream, 0);
		$line = fgets($stream);
		// If the file has no characters, it means it either did not exist
		// or wasn't owned by any process
		// If pgid can't be retrieved, the process that owned the lock
		// doesn't exist anymore
		if (strlen($line) === 0 || posix_getpgid(intval($line)) === false) {
			fseek($stream, 0);
			ftruncate($stream, 0);
			fwrite($stream, getmypid());
			fclose($stream);
			return true;
		}
		if ($strict) {
			throw new \Exception('Error locking: Command already locked by a running process');
		}
		else {
			return false;
		}
	}

	/**
	 * Tries to unlock a lock.
	 * Can fail if the lock file has id of another running process.
	 * @param bool $strict If true, failure throws an exception
	 * @param string $identifier If set, adds a "-$identifier" suffix to the name of the lock file
	 * @return bool true in case of success, false otherwise
	 * @throws \Exception
	 */
	protected function tryUnlock(bool $strict = false, $identifier = null) {
		$folderName = $this->getFolder($identifier);
		if (!file_exists($folderName)) {
			mkdir($folderName, 0777, true);
		}
		$pathName = $this->getPath($identifier);
		$stream = fopen($pathName, 'a+');
		fseek($stream, 0);
		// If the file has no characters, it means it either did not exist
		// or wasn't owned by any process
		// If process id in file matches the current process' id, it is owned
		// by the current process and can be safely removed
		if ($stream === false || strlen(($line = fgets($stream))) === 0 || intval($line) === getmypid()) {
			// Between fclose and unlink calls, the lock is still practically owned by the current process
			// and atomicity can still be guaranteed
			fclose($stream);
			unlink($pathName);
			return true;
		}
		if ($strict) {
			throw new \Exception('Error unlocking: Command already locked by a running process');
		}
		else {
			return false;
		}
	}

	protected function getPath($identifier = null) {
		$fullName = static::getFullName($this->getName(), $identifier);
		if (in_array('nette.safe', stream_get_wrappers())) {
			return 'nette.safe://' . $this->commandLockPathProvider->getPath($fullName);
		}
		else {
			return $this->commandLockPathProvider->getPath($fullName);
		}
	}

	protected function getFolder($identifier = null) {
		$fullName = static::getFullName($this->getName(), $identifier);
		return $this->commandLockPathProvider->getFolder($fullName);
	}

	public static function getFullName($name, $identifier)
	{
		return $name . (is_string($identifier) ? "-$identifier" : '');
	}
}
