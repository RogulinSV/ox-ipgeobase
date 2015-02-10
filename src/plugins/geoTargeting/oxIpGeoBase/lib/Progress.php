<?php
/**
 * Progress
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 24.12.2014 16:52
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Chris Jones <leeked@gmail.com>
 * @encoding utf8
 * @svn $Id$
 * @see Symfony\Component\Console\Helper\ProgressBar
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

class Progress
{
	/**
	 * @var int
	 */
	private $max = 0;

	/**
	 * @var int
	 */
	private $step = 0;

	/**
	 * @var float
	 */
	private $percent = 0.0;

	/**
	 * @var int
	 */
	private $frequency = 0;

	/**
	 * @var float
	 */
	private $time;

	/**
	 * @var callable
	 */
	private $processor;

	/**
	 * @param callable $processor
	 */
	public function __construct(\Closure $processor)
	{
		$this->processor = $processor;
	} // function __construct

	/**
	 * Starts progress
	 *
	 * @param int $max
	 */
	public function start($max)
	{
		$this->max = max(0, (int)$max);
		$this->time = microtime(true);
		$this->step = 0;
		$this->percent = 0.0;

		$this->display();
	} // function start

	/**
	 * Stops progress
	 */
	public function finish()
	{
		$this->setCurrent($this->max);
	} // function finish

	/**
	 * To make a progress
	 *
	 * @param int $step
	 */
	public function advance($step = 1)
	{
		$this->setCurrent($this->step + $step);
	} // function advance

	/**
	 * Sets frequency of progress
	 *
	 * @param int $frequency
	 */
	public function setFrequency($frequency)
	{
		$this->frequency = max(0, (int)$frequency);
	} // function setFrequency

	/**
	 * Sets step of progress
	 *
	 * @param int $step
	 */
	public function setCurrent($step)
	{
		if (is_null($this->time)) {
			throw new \LogicException('You must start the progress bar before calling setCurrent().');
		}

		$step = (int)$step;
		if ($step < $this->step) {
			throw new \LogicException('You can\'t regress the progress bar.');
		}

		if ($this->max > 0 && $step > $this->max) {
			throw new \LogicException('You can\'t advance the progress bar past the max value.');
		}

		$prev = intval($this->step / $this->frequency);
		$curr = intval($step / $this->frequency);
		$this->step = $step;
		$this->percent = $this->max > 0 ? (float)($this->step / $this->max * 100) : 0;

		if ($prev !== $curr or $this->max === $step) {
			$this->display();
		}
	} // function setCurrent

	/**
	 * Gets step of progress
	 *
	 * @return int
	 */
	public function getProgress()
	{
		return $this->step;
	} // function getProgress

	/**
	 * Gets percent of progress
	 *
	 * @return float
	 */
	public function getProgressPercent()
	{
		return $this->percent;
	} // function getProgressPercent

	/**
	 * Gets time of starting
	 *
	 * @return float
	 */
	public function getStartTime()
	{
		return $this->time;
	} // function getStartTime

	/**
	 * Displays results of progress
	 */
	public function display()
	{
		call_user_func_array($this->processor, array(
			$this->getProgress(),
			$this->getProgressPercent(),
			$this->getStartTime()
		));
	} // function display
}