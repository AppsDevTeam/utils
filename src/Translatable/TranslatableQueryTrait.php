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

	protected function getQuery(EntityRepository $repository)
	{
		return $this->applyHints(parent::getQuery($repository));


	}

	public function count(EntityRepository $repository = null, ResultSet $resultSet = null, Paginator $paginatedQuery = null)
	{
		if (is_null($repository)) {
			$repository = $this->em->getRepository($this->getEntityClass());
		}

		if ($queryBuilder = $this->doCreateCountQuery($repository)) {
			return $this->applyHints($queryBuilder->getQuery())->getSingleScalarResult();
		}

		return parent::count($repository, $resultSet, $paginatedQuery);
	}

	private function applyHints(Query $query): Query
	{
		if (is_a($this->getEntityClass(), TranslatableEntityInterface::class, true)) {
			$query->setHint(
				Query::HINT_CUSTOM_OUTPUT_WALKER,
				'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
			);

			$query->setHint(
				TranslatableListener::HINT_TRANSLATABLE_LOCALE,
				$this->locale ?: $this->translator->getLocale()
			);

			$query->setHint(
				TranslatableListener::HINT_FALLBACK,
				true
			);

			if (!$this->defaultLocaleFallback) {
				$query->setHint(
					TranslatableListener::HINT_INNER_JOIN,
					true
				);
			}
		}

		return $query;
	}
}
