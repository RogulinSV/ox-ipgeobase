<?php

require_once MAX_PATH . '/lib/max/Dal/DataObjects/DB_DataObjectCommon.php';

class DataObjects_Ipgeobase extends DB_DataObjectCommon
{
	public $__table = 'ipgeobase';

	public $ip_lbound;
	public $ip_rbound;
	public $ip4_lbound;
	public $ip4_rbound;
	public $country_code;
	public $city_id;

	public $defaultValues = array();

	public function staticGet($class, $k, $v = null)
	{
		return DB_DataObject::staticGet(get_called_class(), $k, $v);
	}
}