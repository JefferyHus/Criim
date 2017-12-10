<?php

/**
 * @package Criim
 * @version 1.0.0
 * @author Jaafari El Housseine <jefferytutorials@gmail.com>
 * @link http://github.com/jefferyhus
 * @copyright All rights reserved
 * @license proprietary
 */

namespace Criim\Core\Input;

use Criim\Core\Input;
use Criim\Core\Arr;

class Instance
{
	/**
	 * @var  array  $get  All GET input
	 */
	protected $input_get = array();

	/**
	 * @var  array  $post  All POST input
	 */
	protected $input_post = array();

	/**
	 * @var  array  $put  All PUT input
	 */
	protected $input_put = array();

	/**
	 * @var  array  $post  All DELETE input
	 */
	protected $input_delete = array();

	/**
	 * @var  array  $input  All PATCH input
	 */
	protected $input_patch = array();

	/**
	 * 
	 */
	public function __construct()
	{
		// fetch global input data
		$this->hydrate();
	}

	/**
	 * Gets the specified GET variable.
	 */
	public function get($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $this->input_get : Arr::get($this->input_get, $index, $default);
	}

	/**
	 * Fetch an item from the POST array
	 */
	public function post($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $this->input_post : Arr::get($this->input_post, $index, $default);
	}

	/**
	 * Fetch an item from the php://input for put arguments
	 */
	public function put($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $this->input_put : \Arr::get($this->input_put, $index, $default);
	}

	/**
	 * Fetch an item from the php://input for patch arguments
	 */
	public function patch($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $this->input_patch : \Arr::get($this->input_patch, $index, $default);
	}

	/**
	 * Fetch an item from the php://input for delete arguments
	 */
	public function delete($index = null, $default = null)
	{
		return (is_null($index) and func_num_args() === 0) ? $this->input_delete : \Arr::get($this->input_delete, $index, $default);
	}

	/**
	 * Return's the input method used (GET, POST, DELETE, etc.)
	 */
	public function method($default = 'GET')
	{
		// if called before a request is active, fall back to the global server setting
		if (env('SERVER_ALLOW_X_HEADERS'))
		{
			return Input::server('HTTP_X_HTTP_METHOD_OVERRIDE', Input::server('REQUEST_METHOD', $default));
		}

		return Input::server('REQUEST_METHOD', $default);
	}

	/**
	 * Hydrates the input array
	 *
	 * @return  void
	 */
	protected function hydrate()
	{
		// get the input method and unify it
		$method = strtolower($this->method());

		// get the content type from the header, strip optional parameters
		$content_header = Input::headers('Content-Type');

		if (($content_type = strstr($content_header, ';', true)) === false)
		{
			$content_type = $content_header;
		}

		// get php raw input
		$php_input = file_get_contents('php://input');

		// handle form-urlencoded input
		if ($content_type == 'application/x-www-form-urlencoded')
		{
			// double-check if max_input_vars is not exceeded,
			// it doesn't always give an E_WARNING it seems...
			if ($method == 'get' or $method == 'post')
			{
				if ($php_input and ($amps = substr_count($php_input, '&')) > ini_get('max_input_vars'))
				{
					throw new \ErrorException(
						'Input truncated by PHP. Number of variables exceeded '.ini_get('max_input_vars').'. To increase the limit to at least the '.$amps.' needed for this HTTP request, change the value of "max_input_vars" in php.ini.',
						1,
						E_WARNING,
						__FILE__,
						__LINE__ + 7 // note: error points to accessing $_POST above!
					);
				}
			}
			else
			{
				// urldecode it if needed
				if (env('SERVER_FORM_URLENCODED'))
				{
					$php_input = urldecode($php_input);
				}

				// other methods than GET and POST need to be parsed manually
				parse_str($php_input, $php_input);
			}
		}
		// handle multipart/form-data input
		elseif ($content_type == 'multipart/form-data')
		{
			// grab multipart boundary from content type header
			preg_match('/boundary=(.*)$/', $content_header, $matches);
			$boundary = $matches[1];

			// split content by boundary and get rid of last -- element
			$blocks = preg_split('/-+'.$boundary.'/', $php_input);
			array_pop($blocks);

			// loop data blocks
			$php_input = array();

			foreach ($blocks as $id => $block)
			{
				// skip empty blocks
				if ( ! empty($block))
				{
					// parse uploaded files
					if (strpos($block, 'application/octet-stream') !== FALSE)
					{
						// match "name", then everything after "stream" (optional) except for prepending newlines
						preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
					}
					// parse all other fields
					else
					{
						// match "name" and optional value in between newline sequences
						preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
					}

					// store the result, if any
					$php_input[$matches[1]] = isset($matches[2]) ? $matches[2] : '';
				}
			}
		}

		// unknown input format
		elseif ($php_input and ! is_array($php_input))
		{
			// don't know how to handle it
			throw new \DomainException('Don\'t know how to parse input of type: '.$content_type);
		}

		// GET and POST input, were not parsed
		$this->input_get = $_GET;
		$this->input_post = $_POST;

		// store the parsed data based on the request method
		if ($method == 'put' or $method == 'patch' or $method == 'delete')
		{
			$this->{'input_'.$method} = $php_input;
		}
	}
}