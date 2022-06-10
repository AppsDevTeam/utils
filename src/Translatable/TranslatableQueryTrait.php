<?php

namespace App\Model\Queries;

use ADT\QueryObjectDataSource\IQueryObject;
use ADT\Utils\Translatable\TranslatableEntityInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Gedmo\Translatable\TranslatableListener;
use ADT\DoctrineComponents\ResultSet;
use Doctrine\ORM\EntityRepository;
use Nette\Localization\ITranslator;

trait TranslatableQueryTrait
{
	protected ?string $locale = null;

	protected ITranslator $translator;

	protected bool $defaultLocaleFallback = true;

	public function setLocale(string $locale): self
	{
		$this->locale = $locale;
		return $this;
	}

	public function setTranslator(ITranslator $translator): self
	{
		$this->translator = $translator;
		return $this;
	}

	public function disableDefaultLocaleFallback(): self
	{
		$this->defaultLocaleFallback = false;
		return $this;
	}

	private function getHints(): array
	{
		$hints = [];

		if (is_a($this->getEntityClass(), TranslatableEntityInterface::class, true)) {
			$hints[Query::HINT_CUSTOM_OUTPUT_WALKER] = 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker';
			$hints[TranslatableListener::HINT_TRANSLATABLE_LOCALE] = $this->locale ?: $this->translator->getLocale();
			$hints[TranslatableListener::HINT_FALLBACK] = true;

			if (!$this->defaultLocaleFallback) {
				$hints[TranslatableListener::HINT_INNER_JOIN] = true;
			}
		}

		return $hints;
	}
}