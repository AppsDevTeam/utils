<?php

namespace ADT\Utils;

use ADT\DoctrineForms\Entity;
use ADT\DoctrineForms\EntityFormMapper;
use ADT\DoctrineForms\Form;
use ADT\Forms\DynamicContainer;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Nette\Forms\Controls\BaseControl;

trait TranslationControlTrait
{
	public function addTranslation(Form $form, $name, $containerFactory)
	{
		$container = $form->addDynamicContainer($name, $containerFactory);
		
		$form
			->setComponentFormMapper($container, function (EntityFormMapper $mapper, DynamicContainer $container, Entity $entity) {
				$meta = $mapper->getMetadata($entity);

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
			->setComponentEntityMapper($container, function (EntityFormMapper $mapper, DynamicContainer $container, Entity $entity) {
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
