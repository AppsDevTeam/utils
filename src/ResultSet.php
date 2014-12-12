<?php
namespace ADT\Utils;

class ResultSet {

	/**
	 * Get pairs from ResultSet
	 * @param \Kdyby\Doctrine\ResultSet $resultSet
	 * @param $key
	 * @param null $value
	 * @return array
	 */
	public static function getPairs(\Kdyby\Doctrine\ResultSet $resultSet, $key, $value = NULL) {
		return array_column($resultSet->toArray(), $value, $key);
	}

}