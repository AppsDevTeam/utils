<?php
namespace ADT\Utils;

use \Doctrine\ORM\AbstractQuery;

class ResultSet {

	/**
	 * Get pairs from ResultSet
	 * @param \Kdyby\Doctrine\ResultSet $resultSet
	 * @param $key
	 * @param null $value
	 * @return array
	 */
	public static function getPairs(\Kdyby\Doctrine\ResultSet $resultSet, $key, $value = NULL, $hydrateObject = FALSE) {
		if ($hydrateObject) {
			$r = [];
			foreach($resultSet as $e) {
				$r[$e->$key] = $e->$value;
			}
			return $r;
		} else {
			return array_column($resultSet->toArray(AbstractQuery::HYDRATE_ARRAY), $value, $key);
		}
	}

}
