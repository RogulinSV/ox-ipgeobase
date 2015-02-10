<?php
/**
 * Task class
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 24.12.2014 15:52
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

require_once MAX_PATH . '/lib/OA/Task.php';
require_once MAX_PATH . '/lib/OA/DB/AdvisoryLock.php';
require_once MAX_PATH . '/lib/OA/Admin/Settings.php';
require_once MAX_PATH . '/lib/pear/System.php';

class Task extends \OA_Task implements LoggerAwareInterface
{
	const LOGGER_CHANNEL = 'maintenance';

	const ACTION_DOWNLOAD = 'download';
	const PROGRESS_DOWNLOAD = 8;
	const ACTION_UNPACK = 'zip';
	const PROGRESS_UNPACK = 2;
	const PROGRESS_IMPORT = 88;
	const PROGRESS_CLEAN = 2;

	/**
	 * @var array
	 */
	private $config;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $url;

	/**
	 * @var string
	 */
	private $path;

	/**
	 * @var bool
	 */
	private $clean = true;

	/**
	 * @var array
	 */
	private $processors = array();

	/**
	 * @var array
	 */
	private $files = array();

	/**
	 * @var array
	 */
	private $errors = array();

	/**
	 * @var Progress
	 */
	private $progress;

	/**
	 * @var Targeting
	 */
	private $targeting;

	/**
	 * Task constructor
	 *
	 * @param array $config
	 */
	public function __construct(array $config)
	{
		$this->config = $config;
		$this->configure();
	} // function __construct

	/**
	 * Static class generator
	 *
	 * @param array $config
	 * @return Task
	 */
	public static function init(array $config)
	{
		return new self($config);
	} // function init

	/**
	 * Checks some task-specific requirements
	 *
	 * @return array
	 */
	public static function supports()
	{
		$output = array();

		$output['curl']['support'] = extension_loaded('curl');
		$output['curl']['message'] = ($output['curl']['support']
			? 'curl extension supported'
			: 'curl extension not supported'
		);

		$output['zip']['support'] = class_exists('ZipArchive');
		$output['zip']['message'] = ($output['zip']['support']
			? 'zip extension supported'
			: 'zip extension not supported'
		);

		$settings = new \OA_Admin_Settings(false);
		$output['config']['support'] = $settings->isConfigWritable();
		$output['config']['message'] = ($output['config']['support']
			? 'configuration file is writable'
			: 'configuration file not writable'
		);

		return $output;
	} // function supports

	/**
	 * Sets logger layer
	 *
	 * @param Logger $logger
	 * @return $this
	 */
	public function setLogger(Logger $logger)
	{
		$this->logger = $logger;

		return $this;
	} // function setLogger

	/**
	 * Sets manually absolute path to archive
	 *
	 * @param string $path
	 * @param bool $clean
	 * @return $this
	 */
	public function setPath($path, $clean = true)
	{
		$this->path = realpath($path);
		$this->clean = (bool)$clean;

		return $this;
	} // function setPath

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
	 * Checks whether there were errors in the process
	 *
	 * @return bool
	 */
	public function hasErrors()
	{
		return (count($this->errors) > 0);
	} // function hasErrors

	/**
	 * Gets last error
	 *
	 * @return string
	 */
	public function getLastError()
	{
		end($this->errors);
		$error = current($this->errors);

		return (string)$error;
	} // function getLastError

	/**
	 * @param string $message
	 */
	private function logDebug($message)
	{
		if ($this->logger) {
			$this->logger->info($message);
		}
	} // function debug

	/**
	 * Configures the task
	 *
	 * @return $this
	 */
	public function configure()
	{
		$this->url = $this->config[Plugin::CONFIG_NAMESPACE]['url'];

		$table = $this->config[Plugin::CONFIG_NAMESPACE]['dbIpTable'];
		$file = $this->config[Plugin::CONFIG_NAMESPACE]['dataIp'];
		$this->files[$table] = $file;
		$this->processors[$table] = array(
			'ip_lbound'     => array('column' => 0, 'processor' => null),
			'ip_rbound'     => array('column' => 1, 'processor' => null),
			'ip4_lbound'    => array('column' => 2, 'processor' => function($value) {
				return Extractor::init()->extractIpLbound($value);
			}),
			'ip4_rbound'    => array('column' => 2, 'processor' => function($value) {
				return Extractor::init()->extractIpRbound($value);
			}),
			'country_code'  => array('column' => 3, 'processor' => null),
			'city_id'       => array('column' => 4, 'processor' => function($value) {
				return Extractor::init()->extractCity($value);
			})
		);

		$table = $this->config[Plugin::CONFIG_NAMESPACE]['dbCitiesTable'];
		$file = $this->config[Plugin::CONFIG_NAMESPACE]['dataCities'];
		$this->files[$table] = $file;
		$this->processors[$table] = array(
			'city_id'       => array('column' => 0, 'processor' => null),
			'city_name'     => array('column' => 1, 'processor' => null),
			'region_name'   => array('column' => 2, 'processor' => null),
			'latitude'      => array('column' => 4, 'processor' => null),
			'longitude'     => array('column' => 5, 'processor' => null)
		);

		return $this;
	} // function configure

	/**
	 * @param int $process
	 * @return Progress
	 */
	private function buildProgress($process)
	{
		$progress = new TaskProgress();

		/**
		 * @param int $step
		 * @param float $percent
		 * @param float $time
		 */
		$processor = function($step, $percent, $time) use($process, $progress) {
			static $notified = false;
			if (!$progress->write($process, $percent)) {
				if (!$notified) {
					trigger_error('Unable to write task progress', E_USER_NOTICE);
					$notified = true;
				}
			}

			if ('cli' === php_sapi_name()) {
				$content = sprintf('%01.4fs %d%%... ', microtime(true) - $time, $percent);
				Console::erase();
				Console::write($content);
			}
		};

		return new Progress($processor);
	} // function buildProgress

	/**
	 * Does the task
	 *
	 * @param int $process Task identifier
	 */
	public function run($process = null)
	{
		$this->errors = array();

		$lock = \OA_DB_AdvisoryLock::factory();
		if ($lock->get(OA_DB_ADVISORYLOCK_MAINTENANCE)) {
			$this->logDebug('run maintenance task');
			$this->progress = $this->buildProgress($process);
			$this->progress->setFrequency(1);
			$this->progress->start(100);

			try {
				if (is_null($this->path)) {
					$this->logDebug('begins downloading the file (url \'' . $this->url . '\')');
					$this->path = $this->download($this->url);
				}
				$this->logDebug('begins file checking (path \'' . $this->path . '\')');
				$this->check($this->path);
				$this->progress->advance(self::PROGRESS_DOWNLOAD);

				$this->logDebug('begins unpacking archive (path \'' . $this->path . '\')');
				$files = $this->unpack($this->path, $this->files);
				$this->progress->advance(self::PROGRESS_UNPACK);

				$tables = array();
				foreach ($files as $table => $path) {
					$this->logDebug('begins database preparing (table \'' . $table . '\')');
					$tables[$table] = $this->prepare($table);

					$this->logDebug('begins importing data into the database  (table \'' . $tables[$table] . '\')');
					$this->import($tables[$table], $this->processors[$table], $path, 1 / count($files));
				}
				$this->progress->setCurrent(self::PROGRESS_IMPORT + self::PROGRESS_DOWNLOAD + self::PROGRESS_UNPACK);

				$this->logDebug('begins saving the configuration data');
				$this->fixate($tables);

				$this->logDebug('begins cleaning');
				$this->clean($this->path);

				/*
				 * TODO: something bad may happen...
				 * TODO: eg. another process load previous config and not finished yet
				 * TODO: is it bad / error?  how to avoid this issue?
				 */
				sleep(5);
				foreach (array_keys($tables) as $table) {
					try {
						$this->flush($table);
					} catch (\Exception $e) {}
				}
				$this->progress->advance(self::PROGRESS_CLEAN);

				$lock->release();
				$this->progress->finish();
			} catch (\RuntimeException $e) {
				$this->logDebug('begins rollback');
				if (!is_null($this->path)) {
					$this->clean($this->path);
				}
				if (isset($tables)) {
					foreach (array_keys($tables) as $table) {
						try {
							$this->flush($tables[$table]);
						} catch (\Exception $e) {}
					}
				}

				$this->logError('errors have been detected during processing the maintenance task: ' . $e->getMessage());
				$lock->release();
			}
		} else {
			$this->logError('errors have been detected during preparing to the maintenance task: unable to acquire the lock');
		}
	} // function run

	/**
	 * Checks the file
	 *
	 * @param string $path
	 * @throws \RuntimeException
	 */
	private function check($path)
	{
		$error = '';

		if (!$error && (!$path or !file_exists($path))) {
			$error = '[filesystem] unable to find archive';
		}

		$elf = '';
		if (!$error) {
			if (false !== ($fp = fopen($path, 'r'))) {
				$elf = fgets($fp, 3);
				fclose($fp);
			} else {
				$error = '[security] unable to check archive';
			}
		}
		if (!$error) {
			if (/*'.zip' !== strtolower(substr($path, -4)) or*/ 'PK' !== $elf) {
				$error = '[security] file is not an archive';
			}
		}

		if ($error) {
			throw new \RuntimeException($error, 0);
		}
	} // function check

	/**
	 * Downloads the archive file
	 *
	 * @param string $url
	 * @return string
	 * @throws \RuntimeException
	 */
	private function download($url)
	{
		$error = '';
		$path = MAX_CACHE . 'ipgeobase_' . \MAX_getRandomNumber(10);

		if (!$error && false === ($ch = curl_init())) {
			$error = '[curl] curl init failed';
		}
		if (!$error && false === ($fp = fopen($path, 'wb'))) {
			$error = '[filesystem] unable to open temporary file';
		}

		if (!$error) {
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->config[Plugin::CONFIG_NAMESPACE]['httpTimeout']);
			curl_setopt($ch, CURLOPT_MAXREDIRS, $this->config[Plugin::CONFIG_NAMESPACE]['httpRedirects']);
			if ($this->config[Plugin::CONFIG_NAMESPACE]['httpProxy']) {
				curl_setopt($ch, CURLOPT_PROXY, $this->config[Plugin::CONFIG_NAMESPACE]['httpProxy']);
				if ($this->config[Plugin::CONFIG_NAMESPACE]['httpAuthName'] or $this->config[Plugin::CONFIG_NAMESPACE]['httpAuthPass']) {
					curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->config[Plugin::CONFIG_NAMESPACE]['httpAuthName'] . ':' . $this->config[Plugin::CONFIG_NAMESPACE]['httpAuthPass']);
				}
			} else if ($this->config[Plugin::CONFIG_NAMESPACE]['httpAuthName'] or $this->config[Plugin::CONFIG_NAMESPACE]['httpAuthPass']) {
				curl_setopt($ch, CURLOPT_USERPWD, $this->config[Plugin::CONFIG_NAMESPACE]['httpAuthName'] . ':' . $this->config[Plugin::CONFIG_NAMESPACE]['httpAuthPass']);
			}
			if ($this->config[Plugin::CONFIG_NAMESPACE]['httpUseragent']) {
				curl_setopt($ch, CURLOPT_USERAGENT, $this->config[Plugin::CONFIG_NAMESPACE]['httpUseragent']);
			}
			if ($this->config[Plugin::CONFIG_NAMESPACE]['httpReferer']) {
				curl_setopt($ch, CURLOPT_REFERER, $this->config[Plugin::CONFIG_NAMESPACE]['httpReferer']);
			}
			curl_setopt($ch, CURLOPT_BUFFERSIZE, 65536);

			if ('cli' === php_sapi_name()) {
				$progress = function ($client, $download, $downloaded, $upload, $uploaded) {
					$content = sprintf('%db / %db... ', $download, $downloaded);
					Console::erase();
					Console::write($content);
				};
				curl_setopt($ch, CURLOPT_NOPROGRESS, false);
				curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, $progress);
			} // if

			curl_exec($ch);
			$status = curl_getinfo($ch);
			$error = curl_error($ch);
			curl_close($ch);
			fclose($fp);
			unset($ch, $fp);
		} // if

		if (!$error) {
			if (!file_exists($path) or filesize($path) !== (int)$status['size_download']) {
				$error = '[curl] unable to download file';
			}
		}

		if ($error) {
			if (isset($fp) && is_resource($fp)) {
				fclose($fp);
			}
			if (isset($ch) && is_resource($ch)) {
				curl_close($ch);
			}
			if (file_exists($path)) {
				unlink($path);
			}

			throw new \RuntimeException($error, 0);
		} // if

		return $path;
	} // function download

	/**
	 * Extracts the archive file
	 *
	 * @param string $path
	 * @param array $files
	 * @return array
	 * @throws \RuntimeException
	 */
	private function unpack($path, array $files)
	{
		$error = '';
		$dir = self::tmpdir($path);

		$close = false;
		if (!$error) {
			$zip = new \ZipArchive();
			if (true !== $zip->open($path, \ZipArchive::CHECKCONS)) {
				$error = '[zip] unable to open archive ' . $path;
			} else {
				$close = true;
			}
		}

		$clean = false;
		if (!$error) {
			if (file_exists($dir) && is_dir($dir)) {
				$error = '[filesystem] temporary dir ' . $dir . ' already exists';
			} else if (!mkdir($dir) or !chmod($dir, 0700)) {
				$error = '[filesystem] unable to create temporary dir ' . $dir;
			} else {
				$clean = true;
			}
		}

		$entries = array();
		foreach ($files as $k => $file) {
			$entries[] = ltrim($file, '\\/');
			$files[$k] = $dir . '/' . $file;
		}

		if (!$error) {
			if (false === $zip->extractTo($dir, $entries)) {
				$error = '[zip] unable to extract files from archive ' . $path . ' to ' . $dir;
			}
		}
		if ($close) {
			$zip->close();
		}

		if ($error) {
			foreach ($files as $file) {
				if (file_exists($file) && is_file($file)) {
					unlink($file);
				}
			}
			if ($clean && file_exists($dir) && is_dir($dir)) {
				rmdir($dir);
			}

			throw new \RuntimeException($error, 0);
		}

		return $files;
	} // function extract

	/**
	 * Imports the data into database
	 *
	 * @param string $table
	 * @param array $processors
	 * @param string $path
	 * @param float $fraction
	 * @throws \RuntimeException
	 */
	private function import($table, array $processors, $path, $fraction)
	{
		$error = '';

		if (false === ($fp = fopen($path, 'r'))) {
			$error = '[filesystem] unable to open temporary file';
		}

		$limit = max((int)$this->config[Plugin::CONFIG_NAMESPACE]['importBufferLimit'], 10);

		/**
		 * @param Progress $progress
		 * @param int $step
		 */
		$progress = function(Progress $progress, $step) {
			static $previousStep = 0;
			static $defaultProgress;
			if (is_null($defaultProgress)) {
				$defaultProgress = $progress->getProgress();
			}

			if ($step !== $previousStep) {
				$progress->setCurrent($defaultProgress + $step);
			}
			$previousStep = $step;
		};
		$fileSize = filesize($path);
		$linesSize = 0;

		$buffer = array();
		if (!$error) {
			$row = 0;
			while (!feof($fp)) {
				$row++;

				if (false !== ($line = fgets($fp, 4096))) {
					$linesSize += (strlen($line) + 1); // + \n
					$columns = explode("\t", $line);

					$values = array();
					foreach ($processors as $field => $params) {
						$column = (int)$params['column'];
						if (!isset($columns[$column])) {
							$error = '[csv] unable to find required data in the ' . $row . ' row, ' . $column . ' column';
							break 2;
						}

						$value = $columns[$column];
						if (!is_null($params['processor'])) {
							$callback = $params['processor'];
							$value = call_user_func_array($callback, array($value));
						}
						$values[$field] = $value;
					} // foreach

					$buffer[] = $values;
					if (count($buffer) >= $limit) {
						try {
							$this->insert($table, array_keys($processors), $buffer);
							$progress($this->progress, intval($linesSize / $fileSize * self::PROGRESS_IMPORT * $fraction));
						} catch (\RuntimeException $e) {
							$error = $e->getMessage();
							break;
						} catch (\Exception $e) {
							$error = '[database] ' . $e->getMessage();
							break;
						}
						$buffer = array();
					}
				} // if
			} // while
		} // if
		if (is_resource($fp)) {
			fclose($fp);
		}

		if (!$error && count($buffer) > 0) {
			try {
				$this->insert($table, array_keys($processors), $buffer);
				$progress($this->progress, intval($linesSize / $fileSize * self::PROGRESS_IMPORT * $fraction));
			} catch (\RuntimeException $e) {
				$error = $e->getMessage();
			} catch (\Exception $e) {
				$error = '[database] ' . $e->getMessage();
			}
		}

		if ($error) {
			throw new \RuntimeException($error, 0);
		}
	} // function import

	/**
	 * Fixates the configuration data
	 *
	 * @param array $tables
	 * @throws \RuntimeException
	 */
	private function fixate(array $tables)
	{
		$settings = Factory::settings($this->config);
		$slice = array_intersect_key($settings, array_flip(array('dbIpTable', 'dbCitiesTable')));
		foreach (array_keys($tables) as $table) {
			foreach ((array)array_search($table, $slice) as $key) {
				$settings[$key] = $tables[$table];
			}
		}
		$settings['dbVersion'] = (int)$settings['dbVersion'] + 1;

		$config = new \OA_Admin_Settings($new = true);
		try {
			$reflection = new \ReflectionObject($config);
			$property = $reflection->getProperty('aConf');
			if (!$property->isPublic()) {
				$property->setAccessible(true);
			}
			$property->setValue($config, Factory::config($fresh = true));
		} catch (\ReflectionException $e) {
			throw new \RuntimeException('[settings] unable to prepare config data', 0);
		}
		if (!$config->isConfigWritable()) {
			throw new \RuntimeException('[settings] config file is not writable', 0);
		}
		$config->bulkSettingChange(Plugin::CONFIG_NAMESPACE, $settings);
		if (!$config->writeConfigChange(null, null, false)) {
			throw new \RuntimeException('[settings] unable to write config changes', 0);
		}
	} // function fixate

	/**
	 * @param string $file
	 * @return bool
	 */
	private function clean($file)
	{
		$output = true;
		if (file_exists($file) && is_file($file)) {
			if ($this->clean) {
				if (!unlink($file)) {
					$output = false;
				}
			}

			$dir = self::tmpdir($file, true);
			if (file_exists($dir) && is_dir($dir)) {
				$pear = new \PEAR();
				$system = new \System();
				if ($pear->isError($system->rm('-rf ' . $dir))) {
					$output = false;
				}
			}
		}

		return $output;
	} // function clean

	/**
	 * @param string $oldTable
	 * @return string
	 * @throws \RuntimeException
	 */
	private function prepare($oldTable)
	{
		$newTable = TaskUtils::evolveTableName($oldTable);

		if (!$this->getTargeting()->prepareTable($oldTable, $newTable)) {
			throw new \RuntimeException('[database] unable to create table' . $newTable, 0);
		}

		return $newTable;
	} // function prepare

	/**
	 * Insert data into DB
	 *
	 * @param string $table
	 * @param array $fields
	 * @param array $buffer
	 * @throws \RuntimeException
	 */
	private function insert($table, array $fields, array $buffer)
	{
		if (!$this->getTargeting()->importData($table, $fields, $buffer)) {
			throw new \RuntimeException('[database] unable to insert into table ' . $table, 0);
		}
	} // function insert

	/**
	 * @param string $table
	 * @return bool
	 * @throws \RuntimeException
	 */
	private function flush($table)
	{
		$targeting = $this->getTargeting();
		if (!TaskUtils::isEvolvedTableName($table, $targeting::defaultTables())) {
			return false;
		}

		if (!$targeting->removeTable($table)) {
			throw new \RuntimeException('[database] unable to remove table ' . $table, 0);
		}

		return true;
	} // function flush

	/**
	 * @return Targeting
	 */
	private function getTargeting()
	{
		if (is_null($this->targeting)) {
			$this->targeting = Factory::targeting($this->config);
		}

		return $this->targeting;
	} // function getTargeting

	/**
	 * @param string $path
	 * @param bool $repeat
	 * @return string
	 */
	private static function tmpdir($path, $repeat = false)
	{
		static $random;
		if (!$random or !$repeat) {
			$random = \MAX_getRandomNumber(32);
		}

		return dirname($path) . '/' . pathinfo(basename($path), PATHINFO_FILENAME) . '_' . $random;
	} // function tmpdir
}