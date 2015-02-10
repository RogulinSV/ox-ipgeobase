<?php

require_once MAX_PATH . '/lib/max/Dal/DataObjects/DB_DataObjectCommon.php';

class DataObjects_Ipgeobase_schedule extends DB_DataObjectCommon
{
	public $__table = 'ipgeobase_schedule';

	public $job_id;
	public $job_code;
	public $job_scheduled;
	public $job_started;
	public $job_completed;
	public $job_status;
	public $job_author;
	public $author_email;
	public $file_location;

	public $defaultValues = array();

	public function staticGet($class, $k, $v = null)
	{
		return DB_DataObject::staticGet(get_called_class(), $k, $v);
	}
}