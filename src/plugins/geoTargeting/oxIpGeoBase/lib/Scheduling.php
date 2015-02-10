<?php
/**
 * DB repository for scheduling
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 24.12.2014 10:05
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

class Scheduling extends AbstractRepository
{
	/**
	 * Version of repository
	 */
	const VERSION = 1;

	/**
	 * Channel for logger
	 */
	const LOGGER_CHANNEL = 'scheduler';

	const STATUS_WAIT = 'wait';
	const STATUS_RUN  = 'run';
	const STATUS_FAIL = 'fail';
	const STATUS_COMPLETE = 'complete';

	/**
	 * @return int
	 */
	public function getVersion()
	{
		return self::VERSION;
	} // function getVersion

	/**
	 * @return \DB_DataObjectCommon
	 */
	private function factory()
	{
		return \OA_Dal::factoryDO('ipgeobase_schedule');
	} // function factory

	/**
	 * @return string
	 */
	private static function codify()
	{
		$code = \MAX_getRandomNumber(32);
		$code = $code . str_shuffle($code);

		return $code;
	} // function codify

	/**
	 * @param string $code
	 * @return bool
	 */
	public static function codified($code)
	{
		return preg_match('/^[a-z0-9]{64}$/', $code);
	} // function codified

	/**
	 * Adds a new task in the scheduler
	 *
	 * @param string $location
	 * @param array $author
	 * @return null|array
	 */
	public function addTask($location, array $author)
	{
		$output = null;

		/** @var \DataObjects_Ipgeobase_schedule $entity */
		$entity = $this->factory();
		$date = date('Y-m-d H:i:s');
		$code = self::codify();

		$entity->job_author = $author['contact_name'];
		$entity->author_email = $author['email_address'];
		$entity->job_scheduled = $date;
		$entity->job_started = null;
		$entity->job_completed = null;
		$entity->job_code = $code;
		$entity->file_location = $location;
		$entity->job_status = self::STATUS_WAIT;

		if ($entity->save()) {
			$output = $entity->toArray();
		} else {
			$this->logError('unable to add a new task in the scheduler');
		}

		return $output;
	} // function addTask

	/**
	 * Opens any scheduled task
	 *
	 * @param int $id
	 * @return bool
	 */
	public function openTask($id)
	{
		$output = false;

		/** @var \DataObjects_Ipgeobase_schedule $entity */
		$entity = $this->factory();
		$date = date('Y-m-d H:i:s');

		$entity->job_id = (int)$id;
		if ($entity->find() && $entity->fetch()) {
			$entity->job_started = $date;
			$entity->job_status = self::STATUS_RUN;

			if ($entity->update()) {
				$output = true;
			} else {
				$this->logError('unable to open task with ID: ' . $id);
			}
		} else {
			$this->logError('unable to find task with ID: ' . $id);
		}

		return $output;
	} // function openTask

	/**
	 * Closes any opened task with specified status
	 *
	 * @param int $id
	 * @param string $status
	 * @return bool
	 */
	public function closeTask($id, $status = self::STATUS_COMPLETE)
	{
		$output = false;

		/** @var \DataObjects_Ipgeobase_schedule $entity */
		$entity = $this->factory();
		$date = date('Y-m-d H:i:s');

		$entity->job_id = (int)$id;
		if ($entity->find() && $entity->fetch()) {
			$entity->job_completed = $date;
			$entity->job_status = (self::STATUS_COMPLETE === $status ? self::STATUS_COMPLETE : self::STATUS_FAIL);

			if ($entity->update()) {
				$output = true;
			} else {
				$this->logError('unable to close task with ID: ' . $id . ' and STATUS: ' . $status);
			}
		} else {
			$this->logError('unable to find task with ID: ' . $id);
		}

		return $output;
	} // function closeTask

	/**
	 * @param int $id
	 * @return array|null
	 */
	public function getTaskById($id)
	{
		$output = null;

		/** @var \DataObjects_Ipgeobase_schedule $entity */
		$entity = $this->factory();

		$entity->job_id = $id;
		if ($entity->find() && $entity->fetch()) {
			$output = $entity->toArray();
		} else if ($this->logger) {
			$this->logger->error('unable to find task with ID: ' . $id);
		}

		return $output;
	} // function getTaskById

	/**
	 * Gets task data by code
	 *
	 * @param string $code
	 * @return null|array
	 */
	public function getTaskByCode($code)
	{
		$output = null;

		/** @var \DataObjects_Ipgeobase_schedule $entity */
		$entity = $this->factory();

		$entity->job_code = $code;
		if ($entity->find() && $entity->fetch()) {
			$output = $entity->toArray();
		} else {
			$this->logError('unable to find task with CODE: ' . $code);
		}

		return $output;
	} // function getTaskByCode

	/**
	 * Gets N latest scheduled tasks
	 *
	 * @param int $limit
	 * @return array
	 */
	public function getLatestTasks($limit = 5)
	{
		$filters = array();
		$options = array(
			'limit' => (int)$limit
		);

		return $this->findTasks($filters, $options);
	} // function getLatestTasks

	/**
	 * Finds some tasks by specific criteria
	 *
	 * @param array $filters [string 'code', array<string> 'status']
	 * @param array $options [int 'limit', array<string,string> 'order']
	 * @return array
	 */
	public function findTasks(array $filters = array(), array $options = array())
	{
		$output = array();
		$builder = $this->factory();
		$table = $builder->tableName();

		if (isset($filters['code'])) {
			$builder->whereAdd($table . '.job_code = ' . $this->escape($filters['code']));
		}
		if (isset($filters['status'])) {
			$statuses = self::constants('STATUS_');
			$filters['status'] = (array)$filters['status'];
			foreach ($filters['status'] as $key => $status) {
				if (!in_array($status, $statuses)) {
					unset($filters['status'][$key]);
				}/* else {
					$filters['status'][$key] = $this->escape($status);
				}*/
			}
			$builder->whereInAdd($table . '.job_status', $filters['status']);
		}

		if (!empty($options['order'])) {
			$order = array();
			foreach ($options['order'] as $field => $direct) {
				$order[] = $table . '.' . $this->db->quoteIdentifier($field) . strtoupper($direct);
			}
			$builder->orderBy(implode(', ', $order));
		} else {
			$builder->orderBy($table . '.job_id DESC');
		}
		if (!empty($options['limit'])) {
			$builder->limit((int)$options['limit']);
		}

		if ($builder->find()) {
			$output = $builder->getAll();
		}

		return $output;
	} // function findTasks

	/**
	 * Finds tasks with status WAIT
	 *
	 * @return array
	 */
	public function findAwaitingTasks()
	{
		return $this->findTasks(array(
			'status' => self::STATUS_WAIT
		));
	} // function findAwaitingTasks

	/**
	 * Finds tasks with status RUN
	 *
	 * @return array
	 */
	public function findRunningTasks()
	{
		return $this->findTasks(array(
			'status' => self::STATUS_RUN
		));
	} // function findRunningTasks

	/**
	 * Finds tasks with status WAIT or RUN
	 *
	 * @return array
	 */
	public function findIncompleteTasks()
	{
		return $this->findTasks(array(
			'status' => array(
				self::STATUS_WAIT,
				self::STATUS_RUN
			)
		));
	} // function findIncompleteTasks

	/**
	 * Finds latest task with status COMPLETE or FAIL
	 *
	 * @return array|null
	 */
	public function findLatestCompleteTask()
	{
		$output = null;

		$filters = array(
			'status' => array(
				self::STATUS_COMPLETE,
				self::STATUS_FAIL
			)
		);
		$options = array(
			'limit' => 1,
			'order' => array(
				'job_completed' => 'desc'
			)
		);
		$tasks = $this->findTasks($filters, $options);
		if ($tasks) {
			$output = array_pop($tasks);
		}

		return $output;
	} // function findLatestCompleteTask

	/**
	 * @return bool
	 */
	public function hasAwaitingTasks()
	{
		return count($this->findAwaitingTasks()) > 0;
	} // function hasAwaitingTasks

	/**
	 * @return bool
	 */
	public function hasRunningTasks()
	{
		return count($this->findRunningTasks()) > 0;
	} // function hasRunningTasks

	/**
	 * @return bool
	 */
	public function hasIncompleteTasks()
	{
		return count($this->findIncompleteTasks()) > 0;
	} // function hasIncompleteTasks

	/**
	 * @param array $task
	 * @return bool
	 */
	public static function isTaskAwaiting(array $task)
	{
		return (
			!empty($task['job_scheduled'])
			&& empty($task['job_started'])
			&& empty($task['job_completed'])
			&& self::STATUS_WAIT === $task['job_status']
		);
	} // function isTaskAwaiting

	/**
	 * @param array $task
	 * @return bool
	 */
	public static function isTaskOpened(array $task)
	{
		return (
			!empty($task['job_scheduled'])
			&& !empty($task['job_started'])
			&& empty($task['job_completed'])
			&& self::STATUS_RUN === $task['job_status']
		);
	} // function isTaskOpened

	/**
	 * @param array $task
	 * @param string $status
	 * @return bool
	 */
	public static function isTaskClosed(array $task, $status = null)
	{
		return (
			!empty($task['job_scheduled'])
			&& !empty($task['job_started'])
			&& !empty($task['job_completed'])
			&& in_array($task['job_status'], array(self::STATUS_COMPLETE, self::STATUS_FAIL))
			&& (is_null($status) or $status === $task['job_status'])
		);
	} // function isTaskClosed

	/**
	 * @param array $task
	 * @param \DateTime $date
	 * @return bool
	 */
	public static function isTaskWasCompletedAt(array $task, \DateTime $date)
	{
		$output = false;
		if (self::isTaskClosed($task)) {
			$diff = $date->diff(new \DateTime($task['job_completed'], $date->getTimezone()));
			$output = ($diff->invert > 0 or 0 === $diff->days);
		}

		return $output;
	} // function isTaskWasCompletedAt
}