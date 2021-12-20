<?php

namespace ADT\Utils;

use Nette\Localization\ITranslator;

trait TLocale
{
	/** @persistent */
	public ?string $locale = null;

	/** @inject */
	public ITranslator $translator;

	public function injectLocale()
	{
		$this->onStartup[] = function() {
			$locale = $this->locale;

			// uzivatel se prepl na jinou lokalizaci pomoci odkazu na strance
			// tuto lokalizaci mu nastavime do cookie, abychom ho odted vzdy presmerovali na tuto lokalizaci,
			// pokud prijde na url, ze ktere neni jasna lokalizace
			if ($this->getParameter('switchToLocale')) {
				if (!in_array($this->getParameter('switchToLocale'), $this->translator->getAvailableLocales())) {
					$this->redirect('this', ['switchToLocale' => null]);
				}

				$locale = $this->getParameter('switchToLocale');

				setcookie('locale', $locale, time() + (3600 * 24 * 14), '/');
			}
			// pokud ma uzivatel lokalizaci jiz ulozenou v cookie,
			// nastavime mu tuto lokalizaci
			elseif (isset($_COOKIE['locale'])) {
				if (!in_array($_COOKIE['locale'], $this->translator->getAvailableLocales())) {
					setcookie('locale', $locale, time() - 1, '/');
					$this->redirect('this');
				}

				$locale = $_COOKIE['locale'];
			}
			// pokud uzivatel dosel na stranky poprve, pokusime se zjistit jazyk prohlizece
			// a podle nej vybrat nejvhodnejsi locale
			else {
				$lang = substr($this->getHttpRequest()->getHeader('Accept-Language'), 0, 2);

				// jestlize je nastaven jazyk prohlizece
				if ($lang) {
					// pokud jsou stranky lokalizovane do jazyka prohlizece,
					// nastavime tuto lokalizaci
					if (in_array($lang, $this->translator->getAvailableLocales())) {
						$locale = $lang;
					}
					// pokud jazyk prohlizece je slovenstina a stranky jsou lokalizovane do cestiny,
					// nastavime cestinu
					elseif ($lang === 'sk' && in_array('cs', $this->translator->getAvailableLocales())) {
						$locale = 'cs';
					}
					// pokud jazyk prohlizece je cestina a stranky jsou lokalizovane do slovenstiny,
					// nastavime slovenstinu
					elseif ($lang === 'cs' && in_array('sk', $this->translator->getAvailableLocales())) {
						$locale = 'sk';
					}
					// pokud nemame stranky lokalizovane do jazyka prohlizece, ale mame je lokalizovane do anglictiny
					// nastavime anglictinu
					elseif (in_array('en', $this->translator->getAvailableLocales())) {
						$locale = 'en';
					}
				}
				// pokud neni nastaven jazyk prohlizece (napriklad u robota) a neni nastavena zadna vychozi lokalizace,
				// nastavime prvni fallback lokalizaci
				elseif ($locale === null) {
					$locale = $this->translator->getFallbackLocales()[0];
				}

				setcookie('locale', $locale, time() + (3600 * 24 * 14), '/');
			}

			// pokud jsme urcili ze nejsme na nejvhodnejsi lokalizaci,
			// presmerujeme
			if ($locale !== $this->locale) {
				$this->redirect('this', ['locale' => $locale, 'originalLocale' => $this->locale]);
			}

			// pokud klikneme na lokalizaci, ktera je jiz aktualni
			if ($this->getParameter('switchToLocale')) {
				$this->redirect('this', ['switchToLocale' => null]);
			}

			$this->locale = $locale;
		};

		$this->onRender[] = function() {
			$this->template->locale = $this->locale;
		};
	}

	public function _()
	{
		return call_user_func_array([$this->translator, 'translate'], func_get_args());
	}
}
