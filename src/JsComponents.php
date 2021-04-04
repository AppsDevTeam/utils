<?php

namespace ADT\Utils;

class JsComponents
{
	protected array $components = [];

	public function generateConfig(): string
	{
		return json_encode($this->components);
	}

	public function setRecaptcha(string $siteKey): string
	{
		return $this->components['recaptcha']['siteKey'] = $siteKey;
	}
}
