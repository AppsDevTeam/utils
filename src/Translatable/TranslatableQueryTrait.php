<?php

namespace App\Model\Queries;

use ADT\QueryObjectDataSource\IQueryObject;
use ADT\Utils\Translatable\TranslatableEntityInterface;
use Doctrine\ORM\Query;
use Gedmo\Translatable\TranslatableListener;
use Kdyby\Persistence\Queryable;
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

	protected function getQuery(Queryable $repository)
	{
		$query = parent::getQuery($repository);
		if (is_a($this->getEntityClass(), TranslatableEntityInterface::class, true)) {
			$query->setHint(
				Query::HINT_CUSTOM_OUTPUT_WALKER,
				'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
			);

			$query->setHint(
				TranslatableListener::HINT_TRANSLATABLE_LOCALE,
				$this->locale ?: $this->translator->getLocale()
			);

			if ($this->defaultLocaleFallback) {
				$query->setHint(
					TranslatableListener::HINT_FALLBACK,
					true
				);

				$query->setHint(
					TranslatableListener::HINT_INNER_JOIN,
					true
				);
			}
		}

		return $query;
	}
}
