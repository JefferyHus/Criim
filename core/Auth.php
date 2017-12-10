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

use Criim\Core\Auth\SimpleAuth;
use Carbon\Carbon;

class Auth
{
	/**
	 * Instance of the Auth class
	 */
	protected static $instance = false;

	/**
	 * Returns an instance of this class
	 */
	public static function getInstance()
	{
		return static::$instance ?: static::$instance = new SimpleAuth();
	}

	/**
	 * Check if there is any valid login
	 */
	public static function check()
	{
		$verified = null;

		if ( static::$instance instanceof SimpleAuth )
		{
			$verified = static::$instance->perform_check();
		}
		else
		{
			$verified = static::getInstance()->perform_check();
		}

		return $verified;
	}

	/**
	 * Calling a function statically
	 */
	public static function __callStatic($method, $arguments)
	{
		return call_Criim_func_array(array(static::getInstance(), $method), $arguments);
	}
}