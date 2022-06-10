<?php

namespace ADT\Utils;

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

	/** @persistent */
	public $url;

	public function __construct(Router $router)
	{
		parent::__construct();

		$this->onStartup[] = function() use ($router) {
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

			$params = $route->match($this->getHttpRequest());

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
					echo "<script>" . PHP_EOL;
					require __DIR__ . '/assets/bot-detector.js';
					echo "new BotDetector({ callback: function(result) { if (!result.isBot) navigator.sendBeacon('" . $this->link('404!', ['referrer' => $this->getHttpRequest()->getReferer() ? $this->getHttpRequest()->getReferer()->getAbsoluteUrl() : null]) . "'); } }).monitor();" . PHP_EOL;
					echo "</script>";
				} elseif (!$this->exception instanceof BadRequestException && $this->log500) {
					Debugger::log($this->exception, ILogger::EXCEPTION);
				}
			});
		};
	}

	public function handle404(?string $referrer)
	{
		Debugger::log('Error 404 with ' . ($referrer ?: 'no' ) . ' referrer (' . $_SERVER['HTTP_USER_AGENT'] . '; ' . $_SERVER['REMOTE_ADDR'] . ')', '404');
		die();
	}
}
