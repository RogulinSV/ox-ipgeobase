<?php

require_once MAX_PATH . '/lib/max/Dal/DataObjects/DB_DataObjectCommon.php';

class DataObjects_Ipgeobase_city extends DB_DataObjectCommon
{
	public $__table = 'ipgeobase_city';

	public $city_id;
	public $city_name;
	public $region_name;
	public $latitude;
	public $longitude;

	public $defaultValues = array();

	public function staticGet($class, $k, $v = null)
	{
		return DB_DataObject::staticGet(get_called_class(), $k, $v);
	}
}