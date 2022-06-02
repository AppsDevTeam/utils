<?php

namespace ADT\Utils\Locale;

use Nette\Application\UI\Presenter;

trait LocaleTrait
{
	/** @persistent */
	public ?string $locale = null;

	public function injectLocale(Presenter $presenter, TranslatorInterface $translator)
	{
		$presenter->onStartup[] = function() use ($presenter, $translator) {
			$locale = $this->locale;

			// uzivatel se prepl na jinou lokalizaci pomoci odkazu na strance
			// tuto lokalizaci mu nastavime do cookie, abychom ho odted vzdy presmerovali na tuto lokalizaci,
			// pokud prijde na url, ze ktere neni jasna lokalizace
			if ($presenter->getParameter('switchToLocale')) {
				if (!in_array($presenter->getParameter('switchToLocale'), $translator->getAvailableLocales())) {
					$presenter->redirect('this', ['switchToLocale' => null]);
				}

				$locale = $presenter->getParameter('switchToLocale');

				setcookie('locale', $locale, time() + (3600 * 24 * 14), '/');
			}
			// pokud ma uzivatel lokalizaci jiz ulozenou v cookie,
			// nastavime mu tuto lokalizaci
			elseif (isset($_COOKIE['locale'])) {
				if (!in_array($_COOKIE['locale'], $translator->getAvailableLocales())) {
					setcookie('locale', $locale, time() - 1, '/');
					$presenter->redirect('this');
				}

				$locale = $_COOKIE['locale'];
			}
			// pokud uzivatel dosel na stranky poprve, pokusime se zjistit jazyk prohlizece
			// a podle nej vybrat nejvhodnejsi locale
			else {
				$lang = substr($presenter->getHttpRequest()->getHeader('Accept-Language'), 0, 2);

				// jestlize je nastaven jazyk prohlizece
				if ($lang) {
					// pokud jsou stranky lokalizovane do jazyka prohlizece,
					// nastavime tuto lokalizaci
					if (in_array($lang, $translator->getAvailableLocales())) {
						$locale = $lang;
					}
					// pokud jazyk prohlizece je slovenstina a stranky jsou lokalizovane do cestiny,
					// nastavime cestinu
					elseif ($lang === 'sk' && in_array('cs', $translator->getAvailableLocales())) {
						$locale = 'cs';
					}
					// pokud jazyk prohlizece je cestina a stranky jsou lokalizovane do slovenstiny,
					// nastavime slovenstinu
					elseif ($lang === 'cs' && in_array('sk', $translator->getAvailableLocales())) {
						$locale = 'sk';
					}
					// pokud nemame stranky lokalizovane do jazyka prohlizece, ale mame je lokalizovane do anglictiny
					// nastavime anglictinu
					elseif (in_array('en', $translator->getAvailableLocales())) {
						$locale = 'en';
					}
				}
				// pokud neni nastaven jazyk prohlizece (napriklad u robota) a neni nastavena zadna vychozi lokalizace,
				// nastavime defaultni lokalizaci
				elseif ($locale === null) {
					$locale = $translator->getDefaultLocale();
				}

				setcookie('locale', $locale, time() + (3600 * 24 * 14), '/');
			}

			// pokud jsme urcili ze nejsme na nejvhodnejsi lokalizaci,
			// presmerujeme
			if ($locale !== $this->locale) {
				$presenter->redirect('this', ['locale' => $locale, 'originalLocale' => $this->locale]);
			}

			// pokud klikneme na lokalizaci, ktera je jiz aktualni
			if ($presenter->getParameter('switchToLocale')) {
				$presenter->redirect('this', ['switchToLocale' => null]);
			}

			$this->locale = $locale;
		};

		$presenter->onRender[] = function() use ($presenter) {
			$presenter->template->locale = $this->locale;
		};
	}
}
