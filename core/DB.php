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

use Opis\Database\Database;
use Opis\Database\Connection;
use Ramsey\Uuid\Uuid;

class DB
{
	/**
	 * Default database settings
	 * @var array
	 */
	protected $settings = [
	    'driver'    => 'mysql',
	    'host'      => 'localhost',
	    'database'  => 'anonime',
	    'username'  => 'root',
	    'password'  => '',
	    'charset'   => 'utf8',
	    'collation' => 'utf8_unicode_ci',
	    'prefix'    => '',
	    'port'		=> 3306
	];

	/**
	 * Instance of DB class
	 */
	protected static $instance = false;

	/**
	 * Connection instance
	 */
	protected $connection = false;

	/**
	 * Construct a database object
	 */
	public function __construct()
	{
		$this->_init();
	}

	/**
	 * Get the instance of this class
	 */
	public static function getInstance()
	{
		if ( is_bool(static::$instance) )
		{
			(new self())->_init();
		}

		return static::$instance;
	}

	/**
	 * Init the settings and the instance
	 */
	public function _init(array $settings = [])
	{
		// if settings are empty then load the env values
		if ( empty($settings) )
		{
			$settings = [
				'driver'   => env('DB_CONNECTION'),
				'host'     => env('DB_HOST'),
				'database' => env('DB_DATABASE'),
				'username' => env('DB_USERNAME'),
				'password' => env('DB_PASSWORD'),
				'port'     => env('DB_PORT'),
				'charset'  => env('DB_CHARSET')
			];
		}
		// merge the given settings with the existing ones
		$this->settings = array_merge($this->settings, $settings);
		// init the connection
		$connection = $this->connect();
		// init the parent constructor
		static::$instance = new Database($connection);
	}

	/**
	 * Generates random keys
	 */
	public static function randomKey($namespace = "php.net")
	{
		try
		{
		    // Generate a version 4 (name-based and hashed with MD5) UUID object
		    $uuid4 = Uuid::uuid4(Uuid::NAMESPACE_DNS, $namespace);
		}
		catch (UnsatisfiedDependencyException $e)
		{
		    // Some dependency was not met. Either the method cannot be called on a
		    // 32-bit system, or it can, but it relies on Moontoast\Math to be present.
		    echo 'Caught exception: ' . $e->getMessage() . "\n";
		}

		return $uuid4->toString(); // i.e. 11a38b9a-b3da-360f-9353-a5a725514269
	}

	/**
	 * Connects a database driver
	 */
	public function connect()
	{
		// create a database dsn first
		extract($this->settings);

		// dsn PDO
		$dsn = "$driver:host=$host;port=$port;dbname=$database";
		
		// if a connection already exists then just return it instead of creating a new one
		if ( ! $this->connection )
		{
			// Create the connection
			$this->connection = (new Connection($dsn, $username, $password))->options(array(
				\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$charset.' COLLATE '.$collation
			));
		}

		return $this->connection;
	}

	/**
	 * When calling a static method
	 */
	public static function __callStatic($method, $arguments)
	{
		return call_Criim_func_array(array(static::getInstance(), $method), $arguments);
	}
} 