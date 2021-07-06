<?php

namespace ADT\Utils\Translatable;

use ADT\DoctrineForms\Entity;
use ADT\DoctrineForms\EntityFormMapper;
use ADT\DoctrineForms\Form;
use ADT\Forms\DynamicContainer;
use App\Components\Forms\Base\EntityForm;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Nette\Forms\Controls\BaseControl;

trait TranslatableSluggableFormTrait
{
	abstract public function getEntityManager(): EntityManager;
	
	abstract protected function getSluggableListener(): SluggableListener;

	/**
	 * @param TranslatableEntityInterface $entity
	 * @throws NonUniqueResultException
	 */
	private function generateSlug(TranslatableEntityInterface $entity)
	{
		/** @var TranslationRepository $repository */
		$repository = $this->getEntityManager()->getRepository('Gedmo\Translatable\Entity\Translation');

		foreach ($repository->findTranslations($entity) as $_locale => $_translation) {
			foreach ($this->getSluggableListener()->getConfiguration($this->getEntityManager(), get_class($entity))['slugs'] as $slugField => $options) {
				$_slugs = [];
				foreach ($options['fields'] as $_field) {
					$slug = $_translation[$_field];
					$slug = $this->getSluggableListener()->getTransliterator()($slug);
					$slug = $this->getSluggableListener()->getUrlizer()($slug);
					$_slugs[] = $slug;
				}
				$slug = implode('-', $_slugs);

				$originalSlug = $slug;
				$counter = 1;
				while (true) {
					try {
						$repository->createQueryBuilder('e')
							->where('e.locale = :locale')
							->andWhere('e.objectClass = :objectClass')
							->andWhere('e.content = :content')
							->andWhere('e.foreignKey != :foreignKey')
							->andWhere('e.field = :field')
							->setParameters([
								'locale' => $_locale,
								'objectClass' => get_class($entity),
								'content' => $slug,
								'foreignKey' => $entity->getId(),
								'field' => $slugField,
							])
							->getQuery()
							->getSingleResult();

						$slug = $originalSlug . '-' . $counter++;
					} catch (NoResultException $e) {
						break;
					}
				}

				$repository->translate($entity, $slugField, $_locale, $slug);
			}
		}
	}
}
