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

use Criim\Core\Input\Instance;

class Input
{
	/**
	 * The input instance
	 */
	protected static $instance = false;

	/**
	 * Returns the instance of the current class
	 */
	public static function getInstance()
	{
		return static::$instance ?: static::$instance = new Instance();
	}

	/**
	 * Calling a function statically
	 */
	public static function __callStatic($method, $arguments)
	{
		return call_Criim_func_array(array(static::getInstance(), $method), $arguments);
	}

	/**
	 * Fetch an item from the SERVER array
	 */
	public static function server($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $_SERVER : Arr::get($_SERVER, strtoupper($index), $default);
	}
	
	/**
	 * Get the public ip address of the user.
	 */
	public static function ip($default = '0.0.0.0')
	{
		return static::server('REMOTE_ADDR', $default);
	}

	/**
	 * Fetch an item from the COOKIE array
	 */
	public static function cookie($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $_COOKIE : Arr::get($_COOKIE, $index, $default);
	}

	/**
	 * Fetch an item from the HTTP request headers
	 */
	public static function headers($index = null, $default = null)
	{
		static $headers = null;

		// do we need to fetch the headers?
		if ($headers === null)
		{
			// deal with fcgi or nginx installs
			if ( ! function_exists('getallheaders'))
			{
				$server = Arr::filter_prefixed(static::server(), 'HTTP_', true);

				foreach ($server as $key => $value)
				{
					$key = join('-', array_map('ucfirst', explode('_', strtolower($key))));
					$headers[$key] = $value;
				}

				$value = static::server('Content_Type', static::server('Content-Type')) and $headers['Content-Type'] = $value;
				$value = static::server('Content_Length', static::server('Content-Length')) and $headers['Content-Length'] = $value;
			}
			else
			{
				$headers = getallheaders();
			}
		}

		return empty($headers) ? $default : ((func_num_args() === 0) ? $headers : Arr::get(array_change_key_case($headers), strtolower($index), $default));
	}
}