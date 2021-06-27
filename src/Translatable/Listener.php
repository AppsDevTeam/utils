<?php

namespace ADT\Utils\Translatable;

class Listener extends \Gedmo\Translatable\TranslatableListener
{
	public function __construct()
	{
		parent::__construct();
		// pridame kvuli tomu, ze jinak spatne funguje viz
		// https://github.com/Atlantic18/DoctrineExtensions/issues/1021
		$this->setSkipOnLoad(true);
	}
}
