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

}
