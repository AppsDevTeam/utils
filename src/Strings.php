<?php

namespace ADT\Utils;

class Strings
{
	/**
	 * Transfer "😊 𝓚𝓪𝓻𝓲𝓷𝓪 𝓒𝓱𝓮𝓫𝓪𝓷 😊" to " Karina Cheban ", but leaves "Tomáš Kudělka" intact
	 * @param $s
	 * @return string
	 */
	public static function toLatin($s)
	{
		return preg_replace_callback(
			"/[^\p{Common}\p{Latin}]|(?:
	          \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
	        | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
	        | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
	    )/x",
			function ($char) {
				return \Nette\Utils\Strings::toAscii($char[0]);
			},
			$s
		);
	}

	/**
	 * Check if string contains characters with code larger than specified code, for example emoticons
	 * You can exclude some characters from check and thus allow them, for example €
	 */
	public static function containsCharactersLargerThen(string $haystack, int $code, string $exclude = ''): bool
	{
		foreach (mb_str_split($haystack) as $c) {
			if (str_contains($exclude, $c)) {
				continue;
			}

			if (mb_ord($c) > $code) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $fullName
	 * @return bool
	 */
	public static function validateFullName(string $fullName): bool
	{
		$pattern = [
			"/([\s])+(-)/" => "-",
			"/(-)([\s])+/" => "-",
			"/([\s])+/" => " ",
		];

		$fullName = trim(preg_replace(array_keys($pattern), array_values($pattern), $fullName));

		return (bool) preg_match("/^
			(?:
			(?=[\-.]*[A-zÀ-ÿěščřžýáíéóúůďťňĎŇŤŠČŘŽÝÁÍÉÚŮĚÓ][\-.]*) (?# Následující slovo obsahuje alespoň jedno písmeno)
			(?:[A-zÀ-ÿěščřžýáíéóúůďťňĎŇŤŠČŘŽÝÁÍÉÚŮĚÓ\-.']){2,} (?# Matchnu slovo o min. dvou znacích)
			|
			- (?# Matchnu pomlčku mezi slovy)
			)
			(?:
			\ *[ ]\ * (?# Oddělovačem slova jsou mezery a .,-')
			(?:
			(?=[\-.]*[A-zÀ-ÿěščřžýáíéóúůďťňĎŇŤŠČŘŽÝÁÍÉÚŮĚÓ][\-.']*) (?# Následující slovo obsahuje alespoň jedno písmeno)
			[A-zÀ-ÿěščřžýáíéóúůďťňĎŇŤŠČŘŽÝÁÍÉÚŮĚÓ\-.,']{2,}
			|
			- (?# Matchnu pomlčku mezi slovy)
			)
			)+ (?# Slov může být více, ale min. dvě)
			$
		/mx", $fullName);
	}
}
