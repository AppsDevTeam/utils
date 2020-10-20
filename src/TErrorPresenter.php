<?php

namespace Adt\Utils;

use ADT\Utils\IRouteFactory;
use App\Modules\WebModule\BasePresenter;
use Nette\Application\BadRequestException;
use Nette\Application\Helpers;
use Nette\Routing\Router;
use Tracy\Debugger;
use Tracy\ILogger;

trait TErrorPresenter
{
	protected $exception;

	public function __construct(Router $router, IRouteFactory $routeFactory)
	{
		parent::__construct();

		$this->onStartup[] = function() use ($router, $routeFactory) {
			$this->exception = $this->getRequest()->getParameter('exception');

			if ($this->exception instanceof BadRequestException) {
				[$moduleName, $presenterName] = Helpers::splitName($this->getName());
				foreach ($router->getRouters() as $routeList) {
					if ($routeList->getModule() === $moduleName . ':') {
						$routeList[] = $route = $routeFactory->create('<url .+>', $presenterName . ':' . $this->getAction());
						break;
					}
				}

				$this->loadState($route->match($this->getHttpRequest()));
			} 
			else {
				Debugger::log($this->exception, ILogger::EXCEPTION);
			}
		};
	}

	public function renderDefault(string $url): void
	{
		
	}
}
