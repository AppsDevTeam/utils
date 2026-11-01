<?php
use ADT\Utils\FileSystem;
use Tester\Assert;

require __DIR__ . '/../vendor/autoload.php';
Tester\Environment::setup();

$base = sys_get_temp_dir() . '/adt-utils-' . uniqid();
$dir = $base . '/a/b/c';

// creates nested directory
Assert::true(FileSystem::createDirAtomically($dir));
Assert::true(is_dir($dir));

// already exists
Assert::false(FileSystem::createDirAtomically($dir));

// mode is applied exactly regardless of umask, including parent directories, and umask is restored
$origUmask = umask(0022);
Assert::same(0770, fileperms($dir) & 0777);
Assert::same(0770, fileperms($base . '/a') & 0777);
$modeDir = $base . '/mode/sub';
Assert::true(FileSystem::createDirAtomically($modeDir, 0700));
Assert::same(0700, fileperms($modeDir) & 0777);
Assert::same(0700, fileperms($base . '/mode') & 0777);
Assert::same(0022, umask());
umask($origUmask);

// a file with the same name exists
$file = $base . '/file';
touch($file);
Assert::exception(
	fn() => FileSystem::createDirAtomically($file),
	Nette\IOException::class,
	"Unable to create directory '$file'.%a%"
);

// a file blocks a parent directory
Assert::exception(
	fn() => FileSystem::createDirAtomically($file . '/sub'),
	Nette\IOException::class
);

// must not depend on error_get_last() - an error handler that swallows errors makes it return null
set_error_handler(fn() => true);
Assert::false(FileSystem::createDirAtomically($dir));
Assert::exception(
	fn() => FileSystem::createDirAtomically($file),
	Nette\IOException::class,
	"Unable to create directory '$file'."
);
restore_error_handler();

// shutdown guard restores umask even after a fatal error, when `finally` is skipped
$script = $base . '/fatal.php';
file_put_contents($script, '<?php
require ' . var_export(__DIR__ . '/../vendor/autoload.php', true) . ';
umask(0022);
ADT\Utils\FileSystem::createDirAtomically(' . var_export($base . '/fatal-dir', true) . '); // registers the guard
register_shutdown_function(fn() => printf("umask-at-shutdown:%04o", umask())); // runs after the guard
umask(0); // simulate umask left unrestored by a fatal error inside the mkdir window
ini_set("memory_limit", "8M");
str_repeat("a", 64 * 1024 * 1024); // fatal error
');
exec(escapeshellarg(PHP_BINARY) . ' -n ' . escapeshellarg($script) . ' 2>&1', $output);
$output = implode("\n", $output);
Assert::contains('Allowed memory size', $output);
Assert::contains('umask-at-shutdown:0022', $output);

Nette\Utils\FileSystem::delete($base);
