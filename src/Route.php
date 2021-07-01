<?php

namespace ADT\Utils;

use Nette\Http\Url;
use Nette\Http\UrlScript;
use Nette\Localization\ITranslator;

interface IRouteFactory
{
	/** @return Route */
	public function create(string $mask, $metadata = [], int $flags = 0, $disableLocaleParameter = false);
}

/**
 * The bidirectional route with non-standard port support for absolute masks.
 */
class Route extends \Nette\Application\Routers\Route
{
	public function __construct(string $mask, $metadata = [], int $flags = 0, $disableLocaleParameter = false, ?ITranslator $translator = null)
	{
		$locale = '';
		if (!$disableLocaleParameter && $translator) {
			$locale = '[<locale' . ($translator->getDefaultLocale() ? '=' . $translator->getDefaultLocale() : '') . ' ' . implode('|', $translator->getAvailableLocales()) . '>/]';
		}

		parent::__construct($locale . $mask, $metadata, $flags);
	}

	/**
	 * Constructs absolute URL from Request object.
	 * @return string|null
	 */
	public function constructUrl(array $params, UrlScript $refUrl): ?string
	{
		$url = parent::constructUrl($params, $refUrl);

		if ($url !== null) {
			$url = new Url($url);
			$url->setQueryParameter('originalLocale', null);
			$url = (string)$url;
		}

		if ($url !== null && !in_array($refUrl->getPort(), Url::$defaultPorts, true)) {
			$nurl = new Url($url);
			$nurl->setPort($refUrl->getPort());
			$url = $nurl->getAbsoluteUrl();
		}

		return $url;
	}
}
