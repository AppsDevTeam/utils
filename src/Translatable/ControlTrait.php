<?php

namespace ADT\Utils\Translatable;

use ADT\BaseForm\EntityForm;
use ADT\DoctrineForms\EntityFormMapper;
use ADT\DoctrineForms\ToManyContainer;
use App\Model\Entity\ITranslatable;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Nette\Forms\Controls\BaseControl;

trait ControlTrait
{
	public function addTranslation(EntityForm $form, $name, $containerFactory)
	{
		$form
			->toMany(
				$name,
				$containerFactory
			)
			->setFormMapper(function (ClassMetadata $meta, ToManyContainer $container, ITranslatable $entity) {
				/** @var EntityFormMapper $mapper */
				$mapper = $this->mapper;

				/** @var TranslationRepository $repository */
				$repository = $mapper->getEntityManager()->getRepository('Gedmo\Translatable\Entity\Translation');
				foreach ($repository->findTranslations($entity) as $_locale => $_translation) {
					$translationEntity = new $entity;
					foreach ($_translation as $field => $value) {
						$meta->setFieldValue($translationEntity, $field, $value);
					}
					$mapper->load($translationEntity, $container[$_locale]);
					$container[$_locale]['locale']->setDefaultValue($_locale);
				}
			})
			->setEntityMapper(function (ClassMetadata $meta, ToManyContainer $container, ITranslatable $entity) {
				/** @var EntityFormMapper $mapper */
				$mapper = $this->mapper;

				// delete removed locales
				$locales = [];
				foreach ($container->getComponents(false) as $_toOneContainer) {
					$locales[$_toOneContainer['locale']->getValue()] = $_toOneContainer['locale']->getValue();
				}

				/** @var TranslationRepository $repository */
				$repository = $mapper->getEntityManager()->getRepository('Gedmo\Translatable\Entity\Translation');
				$repository->createQueryBuilder('e')
					->where('e.objectClass = :objectClass')
					->andWhere('e.locale NOT IN (:locales)')
					->andWhere('e.foreignKey = :foreignKey')
					->setParameters([
						'objectClass' => get_class($entity),
						'locales' => $locales,
						'foreignKey' => $entity->getId()
					])
					->delete()
					->getQuery()
					->execute();

				// create new locales / update existing locales
				foreach ($container->getComponents(false) as $_toOneContainer) {
					/** @var BaseControl $_control */
					foreach ($_toOneContainer->getControls() as $_control) {
						if ($_control->getName() === 'locale') {
							continue;
						}

						$repository->translate($entity, $_control->getName(), $_toOneContainer['locale']->getValue(), $_control->getValue());
					}
				}
			});
	}
}
