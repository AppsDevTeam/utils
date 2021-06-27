<?php

namespace ADT\Utils\Translatable;

interface EntityInterface
{
	public function getId();
	public function setTranslatableLocale($locale);
}
