<?php
declare(strict_types=1);

namespace ADT\Utils\Filters;

use ADT\Utils\FileSystem;
use Exception;
use Nette\Utils\ImageException;
use Nette\Utils\UnknownImageFileException;

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

	/**
	 * @throws ImageException
	 * @throws UnknownImageFileException
	 * @throws Exception
	 */
	public function format(string $url, int $width, int $height, int $mode = \Nette\Utils\Image::FIT, int $format = IMAGETYPE_WEBP): string
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

			list($urlWithoutExtension,) = $this->splitUrlOnLastDot($url);
		}



		$width = $width * $this->multiplier;
		$height = $height * $this->multiplier;
		$newPath = $this->path . '/' . $this->dir;
		$newFileName = $urlWithoutExtension . '_' . $width . '_' . $height . '_' . $mode .  '.' . self::FormatToExtensions[$format];

		// thumbnail already exists
		if (file_exists($newPath . '/' . $newFileName)) {
			goto end;
		}

		FileSystem::createDirAtomically(dirname($newPath . '/' . $newFileName));

		$prevErrorHandler = set_error_handler(function ($errno, $errstr) use (&$prevErrorHandler) {
			if ($errno === E_USER_WARNING && $errstr === 'Nette\Utils\Image::fromFile(): gd-png: libpng warning: iCCP: known incorrect sRGB profile') {
				return true;
			}
			return $prevErrorHandler ? $prevErrorHandler(...func_get_args()) : false;
		});
		if ($isRemoteUrl) {
			$image = \Nette\Utils\Image::fromString($contents);
		} else {
			$image = \Nette\Utils\Image::fromFile($this->path . '/' . $url);
		}
		set_error_handler($prevErrorHandler);
		$image->resize($width, $height, $mode);
		$image->save($newPath . '/' . $newFileName, 100, $format);

		end:

		return '/' . $this->dir . '/' . $newFileName;
	}

	private function isRemoteUrl(string $url): bool
	{
		$parsedUrl = parse_url($url);

		if (isset($parsedUrl['scheme'])) {
			return in_array($parsedUrl['scheme'], ['http', 'https']);
		}

		return false;
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
}
