<?php

namespace ADT\Utils\Translatable;

use Gedmo\Mapping\Annotation\Locale;

trait TranslatableEntityTrait
{
	/**
	 * @Gedmo\Locale
	 * Used locale to override Translation listener`s locale
	 * this is not a mapped field of entity metadata, just a simple property
	 */
	#[Locale]
	private $locale;

	public function setTranslatableLocale($locale)
	{
		$this->locale = $locale;
		return $this;
	}
}
