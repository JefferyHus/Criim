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

use Opis\Database\Model as OModel;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

class Model extends OModel
{
	/**
	 * The model connection
	 */
	protected static $connection;

	/**
	 * Instance of the class
	 */
	protected static $instance = false;

	/**
	 * Returns the instance of the class
	 */
	public static function getInstance()
	{
		return is_bool(static::$instance) ? new self() : static::$instance;
	}

	/**
	 * Gets the connection of PDO driver
	 */
	public static function getConnection()
	{
		return DB::getConnection();
	}

	/**
	 * Generates random keys
	 */
	protected function randomKey($namespace = "php.net")
	{
		try
		{
		    // Generate a version 4 (name-based and hashed with MD5) UUID object
		    $uuid4 = Uuid::uuid4();
		}
		catch (UnsatisfiedDependencyException $e)
		{
		    // Some dependency was not met. Either the method cannot be called on a
		    // 32-bit system, or it can, but it relies on Moontoast\Math to be present.
		    echo 'Caught exception: ' . $e->getMessage() . "\n";
		}

		return $uuid4->toString(); // i.e. 11a38b9a-b3da-360f-9353-a5a725514269
	}
} 