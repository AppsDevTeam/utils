<?php
use Tester\Assert;

require __DIR__ . '/../vendor/autoload.php';
Tester\Environment::setup();

Assert::true(\ADT\Utils\Strings::validateFullname("Ing. Valentýna Lohniská-Kuchařová, Ph.D."), "Ing. Valentýna Lohniská-Kuchařová, Ph.D.");
Assert::true(\ADT\Utils\Strings::validateFullname("Ing. Valentýna Lohniská - Kuchařová, Ph.D."), "Ing. Valentýna Lohniská - Kuchařová, Ph.D.");
Assert::true(\ADT\Utils\Strings::validateFullname("Tomáš Blümer"), "Tomáš Blümer");
Assert::true(\ADT\Utils\Strings::validateFullname("Tomáš Mc'Donald"), "Tomáš Mc'Donald");

Assert::false(\ADT\Utils\Strings::validateFullname("Tomáš"), "Tomáš");
Assert::false(\ADT\Utils\Strings::validateFullname("a b"), "a b");
Assert::false(\ADT\Utils\Strings::validateFullname(".. .."), ".. ..");
Assert::false(\ADT\Utils\Strings::validateFullname("Томáш Куделка"), "Томáш Куделка");
Assert::false(\ADT\Utils\Strings::validateFullname("Tomáš tomas@appsdevteam.com"), "Tomáš tomas@appsdevteam.com");

Assert::false(\ADT\Utils\Strings::validateFullname("ꟻꟻ ꟻꟻ"), "ꟻꟻ ꟻꟻ");
Assert::false(\ADT\Utils\Strings::validateFullname("Jan Novák123"), "Jan Novák123");