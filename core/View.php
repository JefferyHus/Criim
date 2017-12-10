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

use Psr\Http\Message\ResponseInterface;

class View
{
	/**
	 * Template path
	 */
	protected static $template;

	/**
	 * Data to extract into this template
	 */
	protected static $attributes;

	/**
	 * Instance of the class
	 */
	protected static $instance;

	/**
	 * Settings of templates
	 */
	protected static $settings = array(
		'path' => APPPATH . DS . 'views' . DS,
		'type' => 'php',
	);

	/**
	 * get the router
	 */
	protected $router;

	/**
	 * Silent initiation
	 */
	private function __construct()
	{
		global $route;

		$this->router = $route->getContainer()->get('router');
	}

	/**
	 * Get an instance of this class
	 */
	public static function getInstance()
	{
		return static::$instance ?: static::$instance = new self();
	}

	/**
	 * Get The router of the current view
	 */
	public function getRouter()
	{
		return $this->router;
	}

	/**
	 * Will forge 
	 * @param  string $template
	 * @param  array  $attributes
	 * @return this
	 */
	public static function forge($template = "/", $attributes = [], $settings = [])
	{
		if ( ! is_object(static::$instance) )
		{
			static::$instance = new self();
		}

		// if a settings variable is passed then merge it withe the existing values
		if ( ! empty($settings) )
		{
			static::$settings = array_merge(static::$settings, $settings);
		}

		// create the template path according to settings
		static::$template 	= static::$settings['path'] . rtrim($template, '/\\') . '.' . static::$settings['type'];
		static::$attributes = $attributes;

		return static::$instance;
	}

	/**
	 * Renders the template view
	 */
	public function render(ResponseInterface $response = null, array $attributes = [])
	{
		// in cae no response interafce object is passed
		is_null($response) and $response = new ResponseInterface();
		// set attributes
		empty(static::$attributes) and static::$attributes = $attributes;

		$output = $this->fetch(static::$template, static::$attributes);

		$response->getBody()->write($output);

		return $response;
	}

	/**
	 * Will fetch a template given and check if it exists or not, then return the content
	 */
	public function fetch($template, array $attributes = [])
	{
		if ( ! is_file( $template ) )
		{
			throw new \RuntimeException("View cannot render `$template` because the template does not exist, or not a template file.");
		}

		try
		{
			ob_start();

			$this->safeInclude($template, $attributes);

			$output = ob_get_clean();
		}
		catch (\Throwable $e)
		{
			ob_end_clean();

			throw $e;
		}
		catch (\Exception $e)
		{
			ob_end_clean();

			throw $e;
		}

		return $output;
	}

	/**
	 * Includes a template view safely
	 */
	public function safeInclude($template, array $attributes)
	{
		extract($attributes);

		include $template;
	}

	/**
	 * Magic method, returns the output of [static::render].
	 */
	public function __toString()
	{
		try
		{
			return $this->render();
		}
		catch (\Exception $e)
		{
			throw new DomainException($e->getMessage());
		}
	}
}