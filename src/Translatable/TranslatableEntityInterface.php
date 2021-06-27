<?php

namespace ADT\Utils\Translatable;

interface TranslatableEntityInterface
{
	public function getId();
	public function setTranslatableLocale($locale);
}
