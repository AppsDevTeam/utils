<?php
use Tester\Assert;

require __DIR__ . '/../vendor/autoload.php';
Tester\Environment::setup();

Assert::true(\ADT\Utils\Strings::validateFullName("Ing. Valentýna Lohniská-Kuchařová, Ph.D."), "Ing. Valentýna Lohniská-Kuchařová, Ph.D.");
Assert::true(\ADT\Utils\Strings::validateFullName("Ing. Valentýna Lohniská - Kuchařová, Ph.D."), "Ing. Valentýna Lohniská - Kuchařová, Ph.D.");
Assert::true(\ADT\Utils\Strings::validateFullName("Tomáš Blümer"), "Tomáš Blümer");
Assert::true(\ADT\Utils\Strings::validateFullName("Tomáš Mc'Donald"), "Tomáš Mc'Donald");

Assert::false(\ADT\Utils\Strings::validateFullName("Tomáš"), "Tomáš");
Assert::false(\ADT\Utils\Strings::validateFullName("a b"), "a b");
Assert::false(\ADT\Utils\Strings::validateFullName(".. .."), ".. ..");
Assert::false(\ADT\Utils\Strings::validateFullName("Томáш Куделка"), "Томáш Куделка");
Assert::false(\ADT\Utils\Strings::validateFullName("Tomáš tomas@appsdevteam.com"), "Tomáš tomas@appsdevteam.com");

Assert::false(\ADT\Utils\Strings::validateFullName("ꟻꟻ ꟻꟻ"), "ꟻꟻ ꟻꟻ");
Assert::false(\ADT\Utils\Strings::validateFullName("Jan Novák123"), "Jan Novák123");