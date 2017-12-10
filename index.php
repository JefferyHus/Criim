<?php
/**
 * Set error reporting and display errors settings.  You will want to change these when in production.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Europe/London');

defined("DS") or define("DS", "/");
defined("COREPATH") or define("COREPATH", __DIR__ . DS . "core");
defined("VENDOR") or define("VENDOR", __DIR__ . DS . "vendor");
defined("APPPATH") or define("APPPATH", __DIR__ . DS . "app");
defined("PUBLICPATH") or define("PUBLICPATH", __DIR__ . DS . "public");
defined("UPLOADPATH") or define("UPLOADPATH", PUBLICPATH.DS."uploads");
defined("WebRoot") or define("WebRoot", (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);
defined("PublicRoot") or define("PublicRoot", WebRoot.DS."public");

composer_autoload();

/**
 * Composer autoloader
 */
function composer_autoload()
{
	// store the autoloader here
	static $composer;

	if(! $composer) {
		if ( ! is_file( VENDOR . DS . "autoload.php" ) )
		{
			die('Composer is not installed. Please run "php composer.phar update" to install Composer');
		}

		$composer = require(VENDOR . DS . "autoload.php");
	}

	return $composer;
}

/**
 * Create a XHTML tag
 *
 * @param	string			The tag name
 * @param	array|string	The tag attributes
 * @param	string|bool		The content to place in the tag, or false for no closing tag
 * @return	string
 */
if ( ! function_exists('html_tag'))
{
	function html_tag($tag, $attr = array(), $content = false)
	{
		// list of void elements (tags that can not have content)
		static $void_elements = array(
			// html4
			"area","base","br","col","hr","img","input","link","meta","param",
			// html5
			"command","embed","keygen","source","track","wbr",
			// html5.1
			"menuitem",
		);
		// construct the HTML
		$html = '<'.$tag;
		$html .= ( ! empty($attr)) ? ' '.(is_array($attr) ? array_to_attr($attr) : $attr) : '';
		// a void element?
		if (in_array(strtolower($tag), $void_elements))
		{
			// these can not have content
			$html .= ' />';
		}
		else
		{
			// add the content and close the tag
			$html .= '>'.$content.'</'.$tag.'>';
		}
		return $html;
	}
}

/**
 * Moves the uploaded file to the upload directory and assigns it a unique name
 * to avoid overwriting an existing uploaded file.
 *
 * @param string $directory directory to which the file is moved
 * @param UploadedFile $uploaded file uploaded file to move
 * @return string filename of moved file
 */
if ( ! function_exists( 'moveUploadedFile' ) )
{
	function moveUploadedFile($directory, $uploadedFile)
	{
		if ( ! $uploadedFile instanceOf Slim\Http\UploadedFile )
		{
			// get the type of the passed param, if an object then get the class name
			$type     = gettype($uploadedFile);
			$errorMsg = $type === "object" ? "instance of " . get_class($uploadedFile) : $type;

			throw new Exception(sprintf("Argument 2 passed to moveUploadedFile() must be an instance of Slim\Http\UploadedFile, %s given", $errorMsg));
		}

	    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
	    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
	    $filename = sprintf('%s.%0.8s', $basename, $extension);

	    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

	    return $filename;
	}
}

/**
 * Takes an array of attributes and turns it into a string for an html tag
 *
 * @param	array	$attr
 * @return	string
 */
if ( ! function_exists('array_to_attr'))
{
	function array_to_attr($attr)
	{
		$attr_str = '';
		foreach ((array) $attr as $property => $value)
		{
			// Ignore null/false
			if ($value === null or $value === false)
			{
				continue;
			}
			// If the key is numeric then it must be something like selected="selected"
			if (is_numeric($property))
			{
				$property = $value;
			}
			$attr_str .= $property.'="'.str_replace('"', '&quot;', $value).'" ';
		}
		// We strip off the last space for return
		return trim($attr_str);
	}
}

/**
 * Encode the Unicode values to be used in the URI.
 *
 *
 * @param string $utf8_string
 * @param int    $length Max  length of the string
 * @return string String with Unicode encoded for URI.
 */
if ( ! function_exists('utf8_uri_encode') )
{
	function utf8_uri_encode( $utf8_string, $length = 0 ) {
		$unicode = '';
		$values = array();
		$num_octets = 1;
		$unicode_length = 0;

		mbstring_binary_safe_encoding();
		$string_length = strlen( $utf8_string );
		reset_mbstring_encoding();

		for ($i = 0; $i < $string_length; $i++ ) {

			$value = ord( $utf8_string[ $i ] );

			if ( $value < 128 ) {
				if ( $length && ( $unicode_length >= $length ) )
					break;
				$unicode .= chr($value);
				$unicode_length++;
			} else {
				if ( count( $values ) == 0 ) {
					if ( $value < 224 ) {
						$num_octets = 2;
					} elseif ( $value < 240 ) {
						$num_octets = 3;
					} else {
						$num_octets = 4;
					}
				}

				$values[] = $value;

				if ( $length && ( $unicode_length + ($num_octets * 3) ) > $length )
					break;
				if ( count( $values ) == $num_octets ) {
					for ( $j = 0; $j < $num_octets; $j++ ) {
						$unicode .= '%' . dechex( $values[ $j ] );
					}

					$unicode_length += $num_octets * 3;

					$values = array();
					$num_octets = 1;
				}
			}
		}

		return $unicode;
	}
}

/**
 * Set the mbstring internal encoding to a binary safe encoding when func_overload
 * is enabled.
 *
 * When mbstring.func_overload is in use for multi-byte encodings, the results from
 * strlen() and similar functions respect the utf8 characters, causing binary data
 * to return incorrect lengths.
 *
 * This function overrides the mbstring encoding to a binary-safe encoding, and
 * resets it to the users expected encoding afterwards through the
 * `reset_mbstring_encoding` function.
 *
 * It is safe to recursively call this function, however each
 * `mbstring_binary_safe_encoding()` call must be followed up with an equal number
 * of `reset_mbstring_encoding()` calls.
 *
 *
 * @see reset_mbstring_encoding()
 *
 * @staticvar array $encodings
 * @staticvar bool  $overloaded
 *
 * @param bool $reset Optional. Whether to reset the encoding back to a previously-set encoding.
 *                    Default false.
 */
if ( ! function_exists('mbstring_binary_safe_encoding') )
{
	function mbstring_binary_safe_encoding( $reset = false ) {
		static $encodings = array();
		static $overloaded = null;

		if ( is_null( $overloaded ) )
			$overloaded = function_exists( 'mb_internal_encoding' ) && ( ini_get( 'mbstring.func_overload' ) & 2 );

		if ( false === $overloaded )
			return;

		if ( ! $reset ) {
			$encoding = mb_internal_encoding();
			array_push( $encodings, $encoding );
			mb_internal_encoding( 'ISO-8859-1' );
		}

		if ( $reset && $encodings ) {
			$encoding = array_pop( $encodings );
			mb_internal_encoding( $encoding );
		}
	}
}

/**
 * Reset the mbstring internal encoding to a users previously set encoding.
 *
 * @see mbstring_binary_safe_encoding()
 *
 */
if ( ! function_exists('reset_mbstring_encoding') )
{
	function reset_mbstring_encoding() {
		mbstring_binary_safe_encoding( true );
	}
}

/**
 * Checks to see if a string is utf8 encoded.
 *
 * NOTE: This function checks for 5-Byte sequences, UTF8
 *       has Bytes Sequences with a maximum length of 4.
 *
 * @author bmorel at ssi dot fr (modified)
 *
 * @param string $str The string to be checked
 * @return bool True if $str fits a UTF-8 model, false otherwise.
 */
if ( ! function_exists('seems_utf8') )
{
	function seems_utf8( $str )
	{
		mbstring_binary_safe_encoding();
		$length = strlen($str);
		reset_mbstring_encoding();
		for ($i=0; $i < $length; $i++) {
			$c = ord($str[$i]);
			if ($c < 0x80) $n = 0; // 0bbbbbbb
			elseif (($c & 0xE0) == 0xC0) $n=1; // 110bbbbb
			elseif (($c & 0xF0) == 0xE0) $n=2; // 1110bbbb
			elseif (($c & 0xF8) == 0xF0) $n=3; // 11110bbb
			elseif (($c & 0xFC) == 0xF8) $n=4; // 111110bb
			elseif (($c & 0xFE) == 0xFC) $n=5; // 1111110b
			else return false; // Does not match any model
			for ($j=0; $j<$n; $j++) { // n bytes matching 10bbbbbb follow ?
				if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
					return false;
			}
		}
		return true;
	}
}

/**
 * Sanitizes a title, replacing whitespace and a few other characters with dashes.
 *
 * Limits the output to alphanumeric characters, underscore (_) and dash (-).
 * Whitespace becomes a dash.
 *
 *
 * @param string $title     The title to be sanitized.
 * @param string $raw_title Optional. Not used.
 * @param string $context   Optional. The operation for which the string is sanitized.
 * @return string The sanitized title.
 */
if ( ! function_exists('sanitize_title_with_dashes') )
{
	function sanitize_title_with_dashes( $title, $raw_title = '', $context = 'display' )
	{
		$title = strip_tags($title);
		// Preserve escaped octets.
		$title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
		// Remove percent signs that are not part of an octet.
		$title = str_replace('%', '', $title);
		// Restore octets.
		$title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);

		if (seems_utf8($title)) {
			if (function_exists('mb_strtolower')) {
				$title = mb_strtolower($title, 'UTF-8');
			}
			$title = utf8_uri_encode($title, 200);
		}

		$title = strtolower($title);

		if ( 'save' == $context ) {
			// Convert nbsp, ndash and mdash to hyphens
			$title = str_replace( array( '%c2%a0', '%e2%80%93', '%e2%80%94' ), '-', $title );
			// Convert nbsp, ndash and mdash HTML entities to hyphens
			$title = str_replace( array( '&nbsp;', '&#160;', '&ndash;', '&#8211;', '&mdash;', '&#8212;' ), '-', $title );

			// Strip these characters entirely
			$title = str_replace( array(
				// iexcl and iquest
				'%c2%a1', '%c2%bf',
				// angle quotes
				'%c2%ab', '%c2%bb', '%e2%80%b9', '%e2%80%ba',
				// curly quotes
				'%e2%80%98', '%e2%80%99', '%e2%80%9c', '%e2%80%9d',
				'%e2%80%9a', '%e2%80%9b', '%e2%80%9e', '%e2%80%9f',
				// copy, reg, deg, hellip and trade
				'%c2%a9', '%c2%ae', '%c2%b0', '%e2%80%a6', '%e2%84%a2',
				// acute accents
				'%c2%b4', '%cb%8a', '%cc%81', '%cd%81',
				// grave accent, macron, caron
				'%cc%80', '%cc%84', '%cc%8c',
			), '', $title );

			// Convert times to x
			$title = str_replace( '%c3%97', 'x', $title );
		}

		$title = preg_replace('/&.+?;/', '', $title); // kill entities
		$title = str_replace('.', '-', $title);

		$title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
		$title = preg_replace('/\s+/', '-', $title);
		$title = preg_replace('|-+|', '-', $title);
		$title = trim($title, '-');

		return $title;
	}
}

/**
 * Faster equivalent of call_user_func_array
 */
if ( ! function_exists('call_kiraku_func_array'))
{
	function call_kiraku_func_array($callback, array $args)
	{
		// deal with "class::method" syntax
		if (is_string($callback) and strpos($callback, '::') !== false)
		{
			$callback = explode('::', $callback);
		}
		// if an array is passed, extract the object and method to call
		if (is_array($callback) and isset($callback[1]) and is_object($callback[0]))
		{
			// make sure our arguments array is indexed
			if ($count = count($args))
			{
				$args = array_values($args);
			}
			list($instance, $method) = $callback;
			// calling the method directly is faster then call_user_func_array() !
			switch ($count)
			{
				case 0:
					return $instance->$method();
				case 1:
					return $instance->$method($args[0]);
				case 2:
					return $instance->$method($args[0], $args[1]);
				case 3:
					return $instance->$method($args[0], $args[1], $args[2]);
				case 4:
					return $instance->$method($args[0], $args[1], $args[2], $args[3]);
			}
		}
		elseif (is_array($callback) and isset($callback[1]) and is_string($callback[0]))
		{
			list($class, $method) = $callback;
			$class = '\\'.ltrim($class, '\\');
			// calling the method directly is faster then call_user_func_array() !
			switch (count($args))
			{
				case 0:
					return $class::$method();
				case 1:
					return $class::$method($args[0]);
				case 2:
					return $class::$method($args[0], $args[1]);
				case 3:
					return $class::$method($args[0], $args[1], $args[2]);
				case 4:
					return $class::$method($args[0], $args[1], $args[2], $args[3]);
			}
		}
		// if it's a string, it's a native function or a static method call
		elseif (is_string($callback) or $callback instanceOf \Closure)
		{
			is_string($callback) and $callback = ltrim($callback, '\\');
			// calling the method directly is faster then call_user_func_array() !
			switch (count($args))
			{
				case 0:
					return $callback();
				case 1:
					return $callback($args[0]);
				case 2:
					return $callback($args[0], $args[1]);
				case 3:
					return $callback($args[0], $args[1], $args[2]);
				case 4:
					return $callback($args[0], $args[1], $args[2], $args[3]);
			}
		}
		// fallback, handle the old way
		return call_user_func_array($callback, $args);
	}
}

/**
 * Gets the value of an environment variable.
 *
 * @param  string  $key
 * @param  mixed   $default
 * @return mixed
 */
if (! function_exists('env'))
{
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false)
        {
            return value($default);
        }

        switch (strtolower($value))
        {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }

        return $value;
    }
}

/**
 * Return the default value of the given value.
 *
 * @param  mixed  $value
 * @return mixed
 */
if (! function_exists('value'))
{
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

/**
 * If we are working on nginx servers
 */
if (!function_exists('gethttpheaders'))
{
    function gethttpheaders($server = null)
    {
      is_null($server) and $server = $_SERVER;

      $headers = '';

      foreach ($server as $name => $value)
      {
         if (substr($name, 0, 5) == 'HTTP_')
         {
             $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
         }
      }

      return $headers;
    }
}

/**
 * Gets all the rest request headers
 */
if (!function_exists('getserverheaders'))
{
    function getserverheaders($server = null)
    {
      is_null($server) and $server = $_SERVER;

      $headers = '';

      foreach ($server as $name => $value)
      {
         if (substr($name, 0, 5) !== 'HTTP_')
         {
             $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $name))))] = $value;
         }
      }
      
      return $headers;
    }
}

// Load .env variables
$dotenv = new Dotenv\Dotenv(PUBLICPATH);
$dotenv->load();

/**
 * Include routes
 */
$route = route_loader();

// set session handler
/*$route->add(new Kiraku\App\Http\Middleware\SessionMiddleware([
	"name" => "gronpark_session",
	"lifetime" => 3600,
	"domain" => ".granpark.co"
]));*/

$route->add(new \Slim\Middleware\Session([
  'name' => 'dummy_session',
  'autorefresh' => true,
  'lifetime' => '1 hour'
]));

$route->run();

function route_loader()
{
	// store the autoloader here
	static $routes;

	if(! $routes)
	{
		if ( ! is_file( APPPATH . DS . 'http' . DS . 'Routes.php' ) )
		{
			die('Routes are not configured. Please run "create Routes.php inside http folder" to start using routing.');
		}

		$routes = require(APPPATH . DS . 'http' . DS . 'Routes.php');
	}

	return $routes;
}