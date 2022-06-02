<?php

namespace ADT\Utils\Locale;

interface TranslatorInterface
{
	public function getAvailableLocales(): array;
	public function getDefaultLocale(): string;
}