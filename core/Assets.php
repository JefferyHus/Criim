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

class Assets
{
	/**
	 * Add the mtime of the file or not
	 */
	protected $add_mtime = true;

	/**
	 * The file version
	 */
	protected $version = "v1";

	/**
	 * Render the css file content or inline url
	 */
	protected $inline = false;

	/**
	 * List of path fodlers
	 */
	protected $path_folders  = array(
		'css'   => DS . 'css',
		'js'    => DS . 'js',
		'fonts' => DS . 'fonts',
		'images' => DS . 'images',
	);

	/**
	 * List of files to be rendred
	 */
	protected $asset_files = array();

	/**
	 * The Asset folder path
	 */
	protected $asset_folder = "/";

	/**
	 * Instance of the class
	 */
	protected static $instance;

	/**
	 * Init
	 */
	private function __construct($inline, $mtime, $version)
	{
		$this->asset_folder = env('APP_ASSET_FOLDER', 'assets');
		$this->inline = $inline ?: false;
	}

	/**
	 * Create an instance of the class
	 */
	public static function getInstance($inline = false, $mtime = true, $version = "v1")
	{
		return static::$instance ?: static::$instance = new static($inline, $mtime, $version);
	}

	/**
	 * Check if the type and file exists, then renders the file or add it to the
	 * wait list
	 */
	public function asset($type = "css", $filename, $attr = [], $render = true)
	{
		// check if the asset type is supported
		if ( ! array_key_exists($type, $this->path_folders) )
		{
			throw new \OutOfBoundsException("This type of assets {$type} is not supported.");
		}

		// check if the file does exist so we can add it to the list of rendring
		$filepath = $this->asset_folder.$this->path_folders[$type].DS.trim($filename, DS);
		
		if ( ! is_file(PUBLICPATH.DS.$filepath) )
		{
			throw new \OutOfRangeException("The file {$filename} does not exist in the following path: {$filepath};");
		}

		// if the auto rednring is true then call the rendering process
		if ( true == $render )
		{
			return $this->render($type, $filepath, $attr);
		}

		// else add the file to the waiting list
		$this->asset_files[$type][] = array('file' => $filepath, 'attr' => $attr);

		return $this;
	}

	/**
	 * Renders the asset files
	 */
	public function render($type, $filepath, $attr = [])
	{
		// rendering method
		$method = "render_".$type;

		return $this->$method($filepath, $attr);
	}

	/**
	 * Renders the css files
	 */
	public function render_css($filepath, $attr)
	{
		// storage for the result
		$result = '';

		// make sure we have a type
		isset($attr['type']) or $attr['type'] = 'text/css';

		if ( $this->inline )
		{
			$result = html_tag('style', $attr, PHP_EOL.$filepath.PHP_EOL).PHP_EOL;
		}
		else
		{
			if ( ! isset($attr['rel']) or empty($attr['rel']))
			{
				$attr['rel'] = 'stylesheet';
			}

			// if there is a version to be set
			if ( $this->version )
			{
				$filepath .= "?".filemtime(PUBLICPATH . DS . $filepath);
			}

			// add the webroot to the file href path
			$attr['href'] = PublicRoot.DS.$filepath;

			$result = html_tag('link', $attr).PHP_EOL;
		}

		return $result;
	}

	/**
	 * Renders the js files
	 */
	public function render_js($filepath, $attr)
	{
		// storage for the result
		$result = '';

		// make sure we have a type
		isset($attr['type']) or $attr['type'] = 'text/javascript';

		if ( $this->inline )
		{
			$result = html_tag('script', $attr, PHP_EOL.$filepath.PHP_EOL).PHP_EOL;
		}
		else
		{
			// if there is a version to be set
			if ( $this->version )
			{
				$filepath .= "?".filemtime(PUBLICPATH . DS . $filepath);
			}
			
			// add the webroot to the file src path
			$attr['src'] = PublicRoot.DS.$filepath;

			$result = html_tag('script', $attr).PHP_EOL;
		}

		return $result;
	}

	/**
	 * IMG tag renderer
	 */
	protected function render_img($filepath, $attr, $inline)
	{
		// storage for the result
		$result = '';
		// render the image
		$attr['src'] = PublicRoot.DS.$filepath;
		$attr['alt'] = isset($attr['alt']) ? $attr['alt'] : '';
		$result = html_tag('img', $attr );
		// return the result
		return $result;
	}

	/**
	 * Fallback to the instance function
	 */
	public static function __callStatic($method, $arguments)
	{
		// extract the arguments
		$filename 	= $arguments[0];
		$attr 		= isset($arguments[1]) ? $arguments[1] : [];
		$render 	= isset($arguments[2]) ? $arguments[2] : true;

		return static::getInstance()->asset($method, $filename, $attr, $render);
	}
}