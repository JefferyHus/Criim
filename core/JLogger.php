<?php
/**
 * @package Criim
 * @version 1.0.0
 * @author Jaafari El Housseine <jefferytutorials@gmail.com>
 * @link http://github.com/jefferyhus
 * @copyright All rights reserved
 * @license proprietary
 */

namespace Criim\Core;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\AbstractProcessingHandler;

class JLogger
{
    /**
     * No Loggin
     */
    const NONE = 0;

	/**
	 * Monolog instance container
	 */
	protected static $monolog = false;

	/**
	 * log file path
	 */
	protected static $path = null;

	/**
	 * log file filename
	 */
	protected static $filename = null;

	/**
	 * return the monolog instance
	 */
	public static function instance()
	{
		if ( is_bool(static::$monolog) )
		{
			static::_init();
		}

		return static::$monolog;
	}

	/**
	 * create the monolog instance
	 */
	public static function _init()
	{
		static::$monolog = new Logger('gronpark');
		static::initialize();
	}

	/**
	 * Initialize the created the monolog instance
	 */
	public static function initialize()
	{
		// determine the name and location of the logfile
		$path = APPPATH.DS.'logs'.DS;

		// get the required folder permissions
		$permission = env("APP_FOLDER_CHMOD");

		// and make sure it exsts
		if ( ! is_dir($path) or ! is_writable($path))
		{
			throw new \Exception('Unable to create the log file. The configured log path "'.$path.'" does not exist.');
		}

		// determine the name of the logfile
		$filename = env("APP_LOG_FILENAME");
		if (empty($filename))
		{
			$filename = date('Y').DS.date('m').DS.date('d').'.php';
		}

		$fullpath = dirname($filename);
		
		// make sure the log directories exist
		try
		{
			// make sure the full path exists
			if ( ! is_dir($path.$fullpath))
			{
				$basepath = rtrim($path.$fullpath, '\\/').DS;

				if ( !mkdir($basepath, $permission, true) )
				{
					var_dump($basepath);die();
				}

				chmod($basepath, $permission);
			}
			
			// open the file
			$handle = fopen($path.$filename, 'a');
		}
		catch (\Exception $e)
		{
			throw new \Exception('Unable to access the log file. Please check the permissions on '.$path.'. ('.$e->getMessage().')');
		}

		static::$path     = $path;
		static::$filename = $filename;

		if ( ! filesize($path.$filename))
		{
			fwrite($handle, "<?php defined('COREPATH') or exit('No direct script access allowed'); ?>".PHP_EOL.PHP_EOL);
			chmod($path.$filename, env("APP_FILE_CHMOD"));
		}
		fclose($handle);

		// create the streamhandler, and activate the handler
		$stream = new StreamHandler($path.$filename, Logger::DEBUG);

		$formatter = new LineFormatter("%level_name% - %datetime% --> %message%".PHP_EOL, env("APP_LOG_DATE_FORMAT"));

		$stream->setFormatter($formatter);

		static::$monolog->pushHandler($stream);
	}

	/**
	 * Logs a message with the Info Log Level
	 *
	 * @param   string  $msg      The log message
	 * @param   string  $context  The message context
	 * @return  bool    If it was successfully logged
	 */
	public static function info($msg, $context = null)
	{
		return static::write(Logger::INFO, $msg, $context);
	}

	/**
	 * Logs a message with the Debug Log Level
	 *
	 * @param   string  $msg      The log message
	 * @param   string  $context  The message context
	 * @return  bool    If it was successfully logged
	 */
	public static function debug($msg, $context = null)
	{
		return static::write(Logger::DEBUG, $msg, $context);
	}

	/**
	 * Logs a message with the Warning Log Level
	 *
	 * @param   string  $msg      The log message
	 * @param   string  $context  The message context
	 * @return  bool    If it was successfully logged
	 */
	public static function warning($msg, $context = null)
	{
		return static::write(Logger::WARNING, $msg, $context);
	}

	/**
	 * Logs a message with the Error Log Level
	 *
	 * @param   string  $msg      The log message
	 * @param   string  $context  The message context
	 * @return  bool    If it was successfully logged
	 */
	public static function error($msg, $context = null)
	{
		return static::write(Logger::ERROR, $msg, $context);
	}

	/**
	 * Write a log entry to Monolog
	 *
	 * @param	int|string    $level     the log level
	 * @param	string        $msg      the log message
	 * @param	array         $context  message context
	 * @return	bool
	 * @throws	\Exception
	 */
	public static function log($level, $msg, array $context = array())
	{
		// bail out if we don't need logging at all
		if ( ! static::need_logging($level))
		{
			return false;
		}

		// log the message
		static::instance()->log($level, $msg, $context);

		return true;
	}

	/**
	 * Write Log File
	 *
	 * Generally this function will be called using the global log_message() function
	 *
	 * @param	int|string    $level     the log level
	 * @param	string        $msg      the log message
	 * @param	string|array  $context  message context
	 * @return	bool
	 * @throws	\Exception
	 */
	public static function write($level, $msg, $context = null)
	{
		// bail out if we don't need logging at all
		if ( ! static::need_logging($level))
		{
			return false;
		}

		// for compatibility with Monolog contexts
		if (is_array($context))
		{
			return static::log($level, $msg, $context);
		}

		// log the message
		empty($context) ? static::instance()->log($level, $msg) : static::instance()->log($level, $context.' - '.$msg);

		return true;
	}

	/**
	 * Check if a message with this log level needs logging
	 *
	 * @param	int|string    $level     the log level
	 * @return	bool
	 * @throws	\Exception
	 */
	protected static function need_logging($level)
	{
		// defined default error labels
		static $levels = array(
			100 => 'DEBUG',
			200 => 'INFO',
			250 => 'NOTICE',
			300 => 'WARNING',
			400 => 'ERROR',
			500 => 'CRITICAL',
			550 => 'ALERT',
			600 => 'EMERGENCY',
		);

		// defined old default error labels
		static $oldlabels = array(
			1  => 'Error',
			2  => 'Warning',
			3  => 'Debug',
			4  => 'Info',
		);

		// get the levels defined to be logged
		$loglabels = env("APP_LOG_THRESHOLD");

		// bail out if we don't need logging at all
		if ($loglabels == static::NONE)
		{
			// this entry should not be logged
			return false;
		}

		// if it's not an array, assume it's an "up to" level
		if ( ! is_array($loglabels))
		{
			$a = array();
			foreach ($levels as $l => $label)
			{
				$l >= $loglabels and $a[] = $l;
			}
			$loglabels = $a;
		}

		// convert the level to monolog standards if needed
		if (is_int($level) and isset($oldlabels[$level]))
		{
			$level = strtoupper($oldlabels[$level]);
		}
		if (is_string($level))
		{
			if ( ! $level = array_search($level, $levels))
			{
				$level = 250;	// can't map it, convert it to a NOTICE
			}
		}

		// make sure $level has the correct value
		if ((is_int($level) and ! isset($levels[$level])) or (is_string($level) and ! array_search(strtoupper($level), $levels)))
		{
			throw new \Exception('Invalid level "'.$level.'" passed to logger()');
		}
		
		// do we need to log the message with this level?
		if ( ! in_array($level, $loglabels))
		{
			// this entry should not be logged
			return false;
		}

		// this entry should be logged
		return true;
	}
}