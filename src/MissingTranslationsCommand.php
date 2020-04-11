<?php

namespace ADT\Utils;

use Kdyby\Console\Exception\InvalidArgumentException;
use Kdyby\Translation\Translator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MissingTranslationsCommand extends Command {

	/** @var Translator */
	protected $translator;

	protected function configure() {
		$this->setName("adt:missing-translations")->setDescription('Return missing translations.');
		$this->addOption('from', null,InputOption::VALUE_REQUIRED, 'String, for example "en"');
		$this->addArgument('locale', InputArgument::REQUIRED, 'String, for example "en"');
		$this->addUsage('<locale>');
	}

	protected function initialize(InputInterface $input, OutputInterface $output) {
		$this->translator = $this->getHelper("container")->getByType(Translator::class);
	}

	protected function validate(InputInterface $input, OutputInterface $output) {

		if (!$input->getArgument('locale')) {
			throw new InvalidArgumentException('Specify <locale>.');
		}

		return true;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if ($this->validate($input, $output) !== true) {
			return 1;
		}

		$catalogueTo = $this->translator->getCatalogue($input->getArgument('locale'));

		if ($input->getOption('from')) {
			$catalogueFrom = $this->translator->getCatalogue($input->getOption('from'));
		}
		else {
			$catalogueFrom = $this->translator->getCatalogue($this->translator->getDefaultLocale());
		}

		foreach ($catalogueFrom->all() as $domain => $translations) {
			foreach ($translations as $key => $translation) {
				if (!$catalogueTo->defines($key, $domain)) {
					echo $domain . '.' . $key . ': ' . $translation . PHP_EOL;
				}
			}
		}
	}
}
