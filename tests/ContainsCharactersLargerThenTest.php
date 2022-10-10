<?php
use Tester\Assert;

require __DIR__ . '/../vendor/autoload.php';
Tester\Environment::setup();

Assert::false(\ADT\Utils\Strings::containsCharactersLargerThen('Jan Novák', mb_ord('ž')));
Assert::true(\ADT\Utils\Strings::containsCharactersLargerThen('Jan Ɣ Novák', mb_ord('ž')));
Assert::false(\ADT\Utils\Strings::containsCharactersLargerThen('Jan Ɣ Novák', mb_ord('ž'), 'Ɣ'));
Assert::true(\ADT\Utils\Strings::containsCharactersLargerThen('Petr', mb_ord('f')));
Assert::false(\ADT\Utils\Strings::containsCharactersLargerThen('Petr', mb_ord('u')));