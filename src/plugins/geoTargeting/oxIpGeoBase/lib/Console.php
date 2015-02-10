<?php
/**
 * Console
 *
 * @package Revive AdServer
 * @subpackage IpGeoBase Plugin
 * @since 16.01.2015 17:04
 * @author Rogulin
 * @encoding utf8
 * @svn $Id$
 */

namespace OX\plugins\geoTargeting\oxIpGeoBase\lib;

class Console
{
	const EOL = "\n";

	const STDOUT = 'stdout';
	const STDERR = 'stderr';
	const STDIN  = 'stdin';

	const MAX_LENGTH_BYTES = 1024;

	const STYLE_DEFAULT = 0;
	const STYLE_BOLD = 1;
	const STYLE_NORMAL = 3;
	const STYLE_ITALIC = 4;
	const STYLE_NOT_ITALIC = 5;
	const STYLE_UNDERLINE = 6;
	const STYLE_NOT_UNDERLINE = 7;
	const STYLE_DOUBLE_UNDERLINE = 8;
	const STYLE_BLINK = 9;
	const STYLE_NOT_BLINK = 10;
	const STYLE_BLINK_FAST = 11;
	const STYLE_NEGATIVE = 12;
	const STYLE_POSITIVE = 13;
	const STYLE_FAINT = 14;

	private static $styles = array(
		self::STYLE_DEFAULT => '0',
		self::STYLE_BOLD => 1,
		self::STYLE_FAINT => 2,
		self::STYLE_NORMAL  => 22,
		self::STYLE_ITALIC => 3,
		self::STYLE_NOT_ITALIC => 23,
		self::STYLE_UNDERLINE  => 4,
		self::STYLE_DOUBLE_UNDERLINE => 21,
		self::STYLE_NOT_UNDERLINE => 24,
		self::STYLE_BLINK => 5,
		self::STYLE_BLINK_FAST => 6,
		self::STYLE_NOT_BLINK => 25,
		self::STYLE_NEGATIVE => 7,
		self::STYLE_POSITIVE => 27
	);

	const COLOR_DEFAULT = 0;
	const COLOR_GRAY = 1;
	const COLOR_BLACK = 2;
	const COLOR_RED = 3;
	const COLOR_GREEN = 4;
	const COLOR_YELLOW = 5;
	const COLOR_BLUE = 6;
	const COLOR_MAGENTA = 7;
	const COLOR_CYAN = 8;
	const COLOR_WHITE = 9;

	private static $colors = array(
		self::COLOR_GRAY    => 30,
		self::COLOR_BLACK   => 30,
		self::COLOR_RED     => 31,
		self::COLOR_GREEN   => 32,
		self::COLOR_YELLOW  => 33,
		self::COLOR_BLUE    => 34,
		self::COLOR_MAGENTA => 35,
		self::COLOR_CYAN    => 36,
		self::COLOR_WHITE   => 37,
		self::COLOR_DEFAULT => 39
	);
	private static $backgrounds = array(
		self::COLOR_GRAY    => 40,
		self::COLOR_BLACK   => 40,
		self::COLOR_RED     => 41,
		self::COLOR_GREEN   => 42,
		self::COLOR_YELLOW  => 43,
		self::COLOR_BLUE    => 44,
		self::COLOR_MAGENTA => 45,
		self::COLOR_CYAN    => 46,
		self::COLOR_WHITE   => 47,
		self::COLOR_DEFAULT => 49
	);

	/**
	 * Erases line (carriage return)
	 *
	 * @return void
	 */
	public static function erase()
	{
		self::write("\033[1K\033[999D");
	} // function erase

	/**
	 * Resets state
	 *
	 * @return void
	 */
	public static function reset()
	{
		self::decorate(self::$styles[self::STYLE_DEFAULT]);
	} // function reset

	/**
	 * Prints message and exits with code > 0
	 *
	 * @param string $message
	 */
	public static function terminate($message)
	{
		self::error($message);
		self::reset();
		exit(1);
	} // function terminate

	/**
	 * Prints message with coloring in "green"
	 *
	 * @param string $message
	 */
	public static function success($message)
	{
		$message = self::setTextStyle('[OK] ', self::STYLE_BOLD) . $message;
		$message = self::setTextColor($message, self::COLOR_BLACK);
		$message = self::setBgColor($message, self::COLOR_GREEN);
		$message = $message . self::EOL;

		self::write($message, self::STDOUT);
	} // function success

	/**
	 * Prints message with coloring in "red"
	 *
	 * @param string $message
	 */
	public static function error($message)
	{
		$message = self::setTextStyle('[ERR] ', self::STYLE_BOLD) . $message;
		$message = self::setTextColor($message, self::COLOR_WHITE);
		$message = self::setBgColor($message, self::COLOR_RED);
		$message = $message . self::EOL;

		self::write($message, self::STDERR);
	} // function error

	/**
	 * Prints message
	 *
	 * @param string $message
	 * @param string $output
	 */
	public static function write($message, $output = self::STDOUT)
	{
		if (self::STDERR === $output) {
			$output = 'php://stderr';
		} else {
			$output = 'php://stdout';
		}
		if (strlen($message) > self::MAX_LENGTH_BYTES) {
			$message = substr($message, 0, -4) . '... ';
		}

		file_put_contents($output, $message);
	} // function write

	/**
	 * Prints question, returns answer
	 *
	 * @param string $message
	 * @return bool
	 */
	public static function confirm($message)
	{
		$message = $message . ' [y/n] ';
		$message = self::setTextStyle($message, self::STYLE_BOLD);
		$message = self::setTextColor($message, self::COLOR_WHITE);
		$message = self::setBgColor($message, self::COLOR_BLUE);

		self::write($message);

		$message = self::read();
		$message = substr(trim($message), 0, 1);
		$message = strtolower($message);

		return ('y' === $message);
	} // function confirm

	/**
	 * Reads data from standard input
	 *
	 * @return string
	 */
	public static function read()
	{
		return file_get_contents(self::STDIN, null, false, null, -1, self::MAX_LENGTH_BYTES);
	} // function read

	/**
	 * @param string $sequence
	 * @param string $message
	 * @return string
	 */
	private static function decorate($sequence, $message = '')
	{
		return "\033[{$sequence}m".$message;
	} // function decorate

	/**
	 * @param string $message
	 * @param int $color
	 * @return string
	 */
	private static function setTextColor($message, $color)
	{
		$color = (isset(self::$colors[$color]) ? self::$colors[$color] : self::$colors[self::COLOR_DEFAULT]);

		return self::decorate($color, $message) . self::decorate(self::$colors[self::COLOR_DEFAULT]);
	} // function setTextColor

	/**
	 * @param string $message
	 * @param int $color
	 * @return string
	 */
	private static function setBgColor($message, $color)
	{
		$color = (isset(self::$backgrounds[$color]) ? self::$backgrounds[$color] : self::$backgrounds[self::COLOR_DEFAULT]);

		return self::decorate($color, $message) . self::decorate(self::$backgrounds[self::COLOR_DEFAULT]);
	} // function setBgColor

	/**
	 * @param string $message
	 * @param int $style
	 * @return string
	 */
	private static function setTextStyle($message, $style)
	{
		$suffix = null;
		switch ($style) {
			case self::STYLE_BOLD:
			case self::STYLE_FAINT:
				$suffix = 22;
				break;
			case self::STYLE_ITALIC:
				$suffix = 23;
				break;
			case self::STYLE_UNDERLINE:
			case self::STYLE_DOUBLE_UNDERLINE:
				$suffix = 24;
				break;
			case self::STYLE_BLINK:
			case self::STYLE_BLINK_FAST:
				$suffix = 25;
				break;
			case self::STYLE_NEGATIVE:
				$suffix = 27;
				break;
		}

		$style = (isset(self::$styles[$style]) ? self::$styles[$style] : self::$styles[self::STYLE_DEFAULT]);

		$output = self::decorate($style, $message);
		if ($suffix) {
			$output .= self::decorate($suffix);
		}

		return $output;
	} // function setTextStyle
}