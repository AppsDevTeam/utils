<?php

namespace ADT\Utils;

use Nette\Application\Attributes\Persistent;
use Nette\Application\BadRequestException;
use Nette\Application\Helpers;
use Nette\Routing\Router;
use Tracy\Debugger;
use Tracy\ILogger;

trait TErrorPresenter
{
	protected $exception;

	protected bool $log404 = true;
	protected bool $log500 = true;

	#[Persistent]
	public $url;

	public function __construct(Router $router)
	{
		parent::__construct();

		$this->onStartup[] = function() use ($router) {
			// kvuli url typu /adadsa%0D%0ASet-Cookie:crlfinjection=crlfinjection, ktera ani nematchne [<url .*>] catchall routu
			preg_match('#^[^\s]+#', $this->getHttpRequest()->getUrl(), $matches);
			if ($matches[0] !== (string) $this->getHttpRequest()->getUrl()) {
				$this->redirectUrl($matches[0]);
			}

			$this->exception = $this->getRequest()->getParameter('exception');

			// nemusi existovat zadna routa odpovidajici zadane url
			// abychom mohli pouzivat $this->link('this'), musime vytvorit routu, ktera matchne zadanou url
			[$moduleName, $presenterName] = Helpers::splitName($this->getName());

			if ($moduleName) {
				foreach ($router->getRouters() as $_routeList) {
					if ($_routeList->getModule() === $moduleName . ':') {
						/** @var \ADT\Routing\RouteList $routeList */
						$routeList = $_routeList;

						break;
					}
				}
			} else {
				/** @var \ADT\Routing\RouteList $routeList */
				$routeList = $router;
			}

			// vytvorime routu v presnem zneni soucasne url adresy
			$route = $routeList->createRoute('[<url .*>]', $presenterName . ':' . $this->getAction());
			$routeList->prepend($route);

			// routa se nemusi trefit (napriklad kdyz ma modul vlastni masku), a match()
			// pak vraci null - loadState() by na nem skoncil TypeErrorem, tedy chybou 500
			// z kazde neexistujici stranky
			$params = $route->match($this->getHttpRequest()) ?? [];

			// je potreba, aby fungovaly persistentni parametry, napriklad "locale"
			$this->loadState($params);

			// BadRequst muze mit bud kod 404 (neexistuji stranka) nebo 403 (neexistujici handle)
			if ($this->exception instanceof BadRequestException) {
				// je potreba resit rucne, protoze vyhodnocovani signalu probehlo jeste pred FORWARDovanim do ErrorPresenteru
				// v ErrorPresenteru uz se nic nevyhodnocuje
				if (isset($params[static::SIGNAL_KEY]) && $params['do'] === '404') {
					$this->handle404($params['referrer'] ?? null);
				}
			}

			register_shutdown_function(function () {
				if ($this->exception instanceof BadRequestException && $this->log404) {
					echo "<script" . $this->getCspNonceAttribute() . ">" . PHP_EOL;
					require __DIR__ . '/assets/bot-detector.js';
					$link = $this->link('404!', ['referrer' => $this->getHttpRequest()->getReferer() ? $this->getHttpRequest()->getReferer()->getAbsoluteUrl() : null]);
					echo "new BotDetector({ callback: function(result) { if (!result.isBot) navigator.sendBeacon(" . $this->encodeJsString($link) . "); } }).monitor();" . PHP_EOL;
					echo "</script>";
				} elseif (!$this->exception instanceof BadRequestException && $this->log500) {
					Debugger::log($this->exception, ILogger::EXCEPTION);
				}
			});
		};
	}

	/**
	 * Atribut `nonce` pro inline <script>, kdyz aplikace bezi s CSP pouzivajici nonce.
	 *
	 * Bez nej by se skript pri politice bez `'unsafe-inline'` neprovedl a hlaseni 404 by
	 * tise prestalo fungovat. Hodnota se cte z uz odeslane hlavicky, takze pro aplikaci
	 * neni potreba nic nastavovat - staci mit v konfiguraci `script-src: ["'nonce'"]`
	 * (Nette ho dosadi samo, viz HttpExtension).
	 *
	 * Kdyz nonce v politice neni, vraci prazdny retezec a chovani se nemeni.
	 */
	private function getCspNonceAttribute(): string
	{
		foreach (['Content-Security-Policy', 'Content-Security-Policy-Report-Only'] as $header) {
			if (preg_match("~'nonce-([^']+)'~", (string) $this->getHttpResponse()->getHeader($header), $matches)) {
				return ' nonce="' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '"';
			}
		}

		return '';
	}

	/**
	 * Retezcovy literal pro vlozeni do inline <script>.
	 *
	 * Odkaz se sklada z cesty pozadavku, tedy ze vstupu od navstevnika. Driv se vypisoval
	 * mezi rucne psane apostrofy, takze apostrof v ceste retezec ukoncil a zbytek se spustil
	 * jako kod - reflektovane XSS bez prihlaseni, na kterekoli strance 404.
	 *
	 * JSON_HEX_* je tu navic k samotnemu json_encode(): bez nich by v retezci mohlo zustat
	 * `</script>`, ktere ukonci cely skript uz pri parsovani HTML, jeste nez se resi
	 * JavaScript. Lomitka naopak escapovat nepotrebujeme, jen by odkaz znecitelnila.
	 */
	private function encodeJsString(string $value): string
	{
		return json_encode(
			$value,
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);
	}

	public function handle404(?string $referrer)
	{
		Debugger::log('Error 404 with ' . ($referrer ?: 'no' ) . ' referrer (' . $_SERVER['HTTP_USER_AGENT'] . '; ' . $_SERVER['REMOTE_ADDR'] . ')', '404');
		die();
	}
}
