<?php
/**
 * Maintenance
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 14.01.2015 16:16
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

class Maintenance implements LoggerAwareInterface
{
	/**
	 * Channel for logger
	 */
	const LOGGER_CHANNEL = 'maintenance';

	/**
	 * @var array
	 */
	private $config;

	/**
	 * @var Scheduling
	 */
	private $scheduler;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var array
	 */
	private $errors = array();

	/**
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		$this->config = $config;
	} // function __construct

	/**
	 * @param Logger $logger
	 * @return $this
	 */
	public function setLogger(Logger $logger)
	{
		$this->logger = $logger;

		return $this;
	} // function setLogger

	/**
	 * @param string $error
	 */
	private function logError($error)
	{
		$this->errors[] = $error;
		if ($this->logger) {
			$this->logger->error($error);
		}
	} // function logError

	/**
	 * @return bool
	 */
	public function hasErrors()
	{
		return count($this->errors) > 0;
	} // function hasErrors

	/**
	 * @return string
	 */
	public function getLastError()
	{
		end($this->errors);
		$error = current($this->errors);

		return $error;
	} // function getLastError

	/**
	 * @return array
	 */
	public function getErrorsList()
	{
		return $this->errors;
	} // function getErrorsList

	/**
	 * @param string $message
	 */
	private function logDebug($message)
	{
		if ($this->logger) {
			$this->logger->info($message);
		}
	} // function logDebug

	/**
	 * Process scheduled task by code
	 *
	 * @param string $code
	 */
	public function processScheduledTaskByCode($code)
	{
		try {
			$this->checkRuntimeRequirements();

			$task = $this->findScheduledTaskByCode($code);
			$this->processScheduledTask($task);
			$this->logDebug(sprintf('[%d] task successfully processed', $task['job_id']));
		} catch (\RuntimeException $e) {
			for (; $e; $e = $e->getPrevious()) {
				$this->logError($e->getMessage());
			}
		}
	} // function processScheduledTaskByCode

	/**
	 * Process latest scheduled task
	 *
	 * @param string $user
	 */
	public function processLatestScheduledTask($user)
	{
		try {
			$this->checkRuntimeRequirements();

			$limit = (int)$this->config['staleLimit'];
			$task = $this->findLatestScheduledTask();
			if ($task) {
				$this->processScheduledTask($task);
				$this->logDebug(sprintf('[%d] scheduled task successfully processed', $task['job_id']));
			} else if ($this->isTasksStaled($limit)) {
				$task = $this->scheduleTask($user);
				$this->logDebug(sprintf('[%d] newly created task is successfully scheduled', $task['job_id']));
				$this->processScheduledTask($task);
				$this->logDebug(sprintf('[%d] recently scheduled task successfully processed', $task['job_id']));
			} else {
				$this->logDebug('scheduled tasks not found');
			}
		} catch (\RuntimeException $e) {
			for (; $e; $e = $e->getPrevious()) {
				$this->logError($e->getMessage());
			}
		}
	} // function processLatestScheduledTask

	/**
	 * @throws \RuntimeException
	 */
	private function checkRuntimeRequirements()
	{
		$errors = array();
		foreach (Task::supports() as $supports) {
			if (true !== $supports['support']) {
				$errors[] = $supports['message'];
			}
		}
		if ($errors) {
			throw new \RuntimeException(sprintf('server configuration does not meet the requirements: %s', implode(', ', $errors)), 0);
		}
	} // function checkRuntimeRequirements

	/**
	 * @param string $code
	 * @return array
	 * @throws \RuntimeException
	 */
	private function findScheduledTaskByCode($code)
	{
		$scheduler = $this->getScheduler();
		if (!$scheduler::codified($code)) {
			throw new \RuntimeException('task code is not specified', 0);
		}

		$output = $scheduler->getTaskByCode($code);
		if (!$output) {
			throw new \RuntimeException('unknown task code was specified', 0);
		}

		return $output;
	} // function findScheduledTaskByCode

	/**
	 * @return array|null
	 */
	private function findLatestScheduledTask()
	{
		$output = null;

		$scheduler = $this->getScheduler();
		$tasks = $scheduler->findAwaitingTasks();
		if (count($tasks) > 0) {
			$output = array_pop($tasks);
		}

		return $output;
	} // function findLatestScheduledTask

	/**
	 * @param array $data
	 * @throws \RuntimeException
	 */
	private function processScheduledTask(array $data)
	{
		$scheduler = $this->getScheduler();

		if ($data['file_location'] && !Filesystem::isSafePath($data['file_location'])) {
			throw new \RuntimeException(sprintf('[%d] incorrectly specified parameter \'file\' "%s" for the task', $data['job_id'], $data['file_location']), 0);
		} else if (!$scheduler::isTaskAwaiting($data)) {
			if ($scheduler::isTaskOpened($data)) {
				throw new \RuntimeException(sprintf('[%d] specified task is running', $data['job_id']), 0);
			} else if ($scheduler::isTaskClosed($data)) {
				throw new \RuntimeException(sprintf('[%d] specified task is closed', $data['job_id']), 0);
			}
		} // if

		if (!$scheduler->openTask($data['job_id'])) {
			throw new \RuntimeException(sprintf('[%d] failed to start the specified task', $data['job_id']), 0);
		}

		$task = Factory::task($this->config);
		if ($data['file_location']) {
			$task->setPath($data['file_location']);
		}
		$task->run(intval($data['job_id']));

		if (!$task->hasErrors()) {
			if (!$scheduler->closeTask($data['job_id'], $scheduler::STATUS_COMPLETE)) {
				throw new \RuntimeException(sprintf('[%d] failed to close the specified task with status %s', $data['job_id'], $scheduler::STATUS_COMPLETE), 0);
			}
		} else {
			if (!$scheduler->closeTask($data['job_id'], $scheduler::STATUS_FAIL)) {
				throw new \RuntimeException(sprintf('[%d] failed to close the specified task with status %s', $data['job_id'], $scheduler::STATUS_FAIL), 0,
					new \RuntimeException(sprintf('[%d] failed to process the specified task: %s', $data['job_id'], $task->getLastError()), 0)
				);
			} else {
				throw new \RuntimeException(sprintf('[%d] failed to process the specified task: %s', $data['job_id'], $task->getLastError()), 0);
			}
		} // if
	} // function processScheduledTask

	/**
	 * @param int $limit
	 * @return bool
	 */
	private function isTasksStaled($limit)
	{
		$output = false;

		$scheduler = $this->getScheduler();
		if (!$scheduler->hasRunningTasks()) {
			if (!!($task = $scheduler->findLatestCompleteTask())) {
				$zone  = new \DateTimeZone(\date_default_timezone_get());
				$date  = new \DateTime('now - ' . $limit . ' day', $zone);
				$output = $scheduler::isTaskWasCompletedAt($task, $date);
			} else if (!$scheduler->hasIncompleteTasks()) {
				$output = true;
			}
		}

		return $output;
	} // function isTasksStaled

	/**
	 * @param string $user
	 * @return array
	 * @throws \RuntimeException
	 */
	private function scheduleTask($user)
	{
		$scheduler = $this->getScheduler();
		$output = $scheduler->addTask(null, array(
			'contact_name'  => $user,
			'email_address' => ''
		));
		if (!$output) {
			throw new \RuntimeException('unable to add new task in schedule');
		}

		return $output;
	} // function scheduleTask

	/**
	 * @return Scheduling
	 */
	private function getScheduler()
	{
		if (is_null($this->scheduler)) {
			$this->scheduler = Factory::scheduling($this->config);
		}

		return $this->scheduler;
	} // function getScheduler
}