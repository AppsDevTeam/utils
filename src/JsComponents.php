<?php

namespace ADT\Utils;

use Nette\Utils\Json;

class JsComponents
{
	protected array $components = [];

	public function generateConfig(): string
	{
		return Json::encode($this->components);
	}

	public function setRecaptcha(string $siteKey): string
	{
		return $this->components['recaptcha']['siteKey'] = $siteKey;
	}

	public function setComponents(array $components): self
	{
		$this->components = array_merge($this->components, $components);
		return $this;
	}
}
