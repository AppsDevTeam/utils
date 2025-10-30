<?php
declare(strict_types=1);

namespace ADT\Utils\Filters;

use ADT\Utils\FileSystem;
use Exception;
use Nette\Utils\ImageException;

class Image
{
	private string $path;
	private string $dir;
	private int $multiplier;

	public function __construct(string $path, string $dir = 'thumbnails', int $multiplier = 2)
	{
		$this->path = $path;
		$this->dir = $dir;
		$this->multiplier = $multiplier;
	}

	const FormatToExtensions = [
		IMAGETYPE_WEBP => 'webp'
	];

	const ExtensionsToFormat = [
		'webp' => IMAGETYPE_WEBP
	];

	/**
	 * @throws Exception
	 */
	public function format(string $url, int $width, int $height, int $mode = \Nette\Utils\Image::OrSmaller, int $format = IMAGETYPE_WEBP): string
	{
		$isRemoteUrl = $this->isRemoteUrl($url);

		// original file does not exist
		if ($isRemoteUrl) {
			$contents = @file_get_contents($url);
			if (!$contents) {
				return $url;
			}

			list($urlWithoutExtension,) = $this->splitUrlOnLastDot($this->removeProtocol($url));

		} else {
			$url = trim($url, '/');
			if (!file_exists($this->path . '/' . $url))  {
				return $url;
			}
			$contents = file_get_contents($this->path . '/' . $url);

			if ($this->isAnimatedGif($contents)) {
				return $url;
			}

			list($urlWithoutExtension,) = $this->splitUrlOnLastDot($url);
		}

		$width = $width * $this->multiplier;
		$height = $height * $this->multiplier;
		$ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
		$newFile = $this->dir . '/' . $urlWithoutExtension . '_' . $width . '_' . $height . '_' . $mode . '_' . $ext . '.' . self::FormatToExtensions[$format];

		$this->createImage($contents, $width, $height, $mode, $format, $this->path . '/' . $newFile);

		return '/' . $newFile;
	}

	private function isRemoteUrl(string $url): bool
	{
		$parsedUrl = parse_url($url);

		if (isset($parsedUrl['scheme'])) {
			return in_array($parsedUrl['scheme'], ['http', 'https']);
		}

		return false;
	}

	/**
	 * Thanks to ZeBadger for original example, and Davide Gualano for pointing me to it
	 * Original at http://it.php.net/manual/en/function.imagecreatefromgif.php#59787
	 **/
	private function isAnimatedGif($fileContents): bool
	{
		$raw = $fileContents;

		$offset = 0;
		$frames = 0;
		while ($frames < 2)
		{
			$where1 = strpos($raw, "\x00\x21\xF9\x04", $offset);
			if ( $where1 === false )
			{
				break;
			}
			else
			{
				$offset = $where1 + 1;
				$where2 = strpos( $raw, "\x00\x2C", $offset );
				if ( $where2 === false )
				{
					break;
				}
				else
				{
					if ( $where1 + 8 == $where2 )
					{
						$frames ++;
					}
					$offset = $where2 + 1;
				}
			}
		}

		return $frames > 1;
	}

	private function splitUrlOnLastDot(string $url): array
	{
		$lastDotPos = strrpos($url, '.');

		if ($lastDotPos !== false) {
			$part1 = substr($url, 0, $lastDotPos);
			$part2 = substr($url, $lastDotPos + 1);
			return [$part1, $part2];
		}

		return [$url, ''];
	}

	private function removeProtocol(string $url): string
	{
		return preg_replace("/^https?:\/\//", "", $url);
	}

	/**
	 * @throws ImageException
	 */
	public function createImageFromThumbnailUrl(string $url): bool
	{
		$info = pathinfo($url);
		if (empty($info['extension'])) {
			return false;
		}
		$format = $info['extension'];
		if (!array_key_exists($format, static::ExtensionsToFormat)) {
			return false;
		}

		if (empty($info['filename'])) {
			return false;
		}
		$fileInfo = pathinfo($info['filename']);

		if (empty($fileInfo['filename'])) {
			return false;
		}
		$segments = explode('_', $fileInfo['filename']);

		$extension = array_pop($segments);
		$mode = (int)array_pop($segments);
		$height = (int)array_pop($segments);
		$width = (int)array_pop($segments);
		$originalFile = $info['dirname'] . '/' . implode('_', $segments) . '.' . $extension;
		if (!file_exists($this->path . '/' . $originalFile)) {
			return false;
		}
		$this->createImage(file_get_contents($this->path . '/' . $originalFile), $width, $height, $mode, static::ExtensionsToFormat[$format], $this->path . '/' . $this->dir . '/' . $url);

		return true;
	}

	/**
	 * @throws ImageException
	 * @throws Exception
	 */
	protected function createImage(string $contents, int $width, int $height, int $mode, int $format, string $newFile): void
	{
		// thumbnail already exists
		if (file_exists($newFile)) {
			return;
		}

		FileSystem::createDirAtomically(dirname($newFile));

		$prevErrorHandler = set_error_handler(function ($errno, $errstr) use (&$prevErrorHandler) {
			if ($errno === E_USER_WARNING && $errstr === 'Nette\Utils\Image::fromString(): gd-png: libpng warning: iCCP: known incorrect sRGB profile') {
				return true;
			}
			return $prevErrorHandler ? $prevErrorHandler(...func_get_args()) : false;
		});
		$image = \Nette\Utils\Image::fromString($contents);
		set_error_handler($prevErrorHandler);
		$image->resize($width, $height, $mode);
		$image->save($newFile, 100, $format);
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getDir(): string
	{
		return $this->dir;
	}
}
