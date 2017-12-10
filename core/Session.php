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

use SessionHandlerInterface;

class Session implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /**
     * Whether to keep flash data or not
     */
    protected $keepFlash = false;
    /**
     * Get a session variable.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        $keys = explode('.', $key);

        $array = isset( $_SESSION ) ? $_SESSION : null;

        if ( ! is_array( $array ) || null === $array )
        {
            return $default;
        }

        foreach ( $keys as $key )
        {
            if ( array_key_exists( $key, $array ) )
            {
                $array = $array[$key];
            }
            else
            {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Static get
     */
    public static function _get($key, $default = null)
    {
        $keys = explode('.', $key);

        $array = isset( $_SESSION ) ? $_SESSION : null;

        if ( ! is_array( $array ) || null === $array )
        {
            return $default;
        }

        foreach ( $keys as $key )
        {
            if ( array_key_exists( $key, $array ) )
            {
                $array = $array[$key];
            }
            else
            {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Set a session variable.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value = null)
    {
        if ( is_array( $key ) )
        {
            foreach ($key as $k => $v)
            {
                $_SESSION[$k] = $v;
            }
        }
        else
        {
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Static set
     */
    public static function _set($key, $value = null)
    {
        if ( is_array( $key ) )
        {
            foreach ($key as $k => $v)
            {
                $_SESSION[$k] = $v;
            }
        }
        else
        {
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Delete a session variable.
     *
     * @param string $key
     *
     * @return $this
     */
    public function delete($key)
    {
        if ($this->exists($key)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Delete a session variable.
     *
     * @param string $key
     *
     * @return $this
     */
    public static function _delete($key)
    {
        if (static::_exists($key)) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Clear all session variables.
     *
     * @return $this
     */
    public function clear()
    {
        $_SESSION = [];

        return $this;
    }

    /**
     * Check if a session variable is set.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return array_key_exists($key, $_SESSION);
    }

    /**
     * Check if a session variable is set.
     *
     * @param string $key
     *
     * @return bool
     */
    public static function _exists($key)
    {
        return array_key_exists($key, $_SESSION);
    }

    /**
     * Get or regenerate current session ID.
     *
     * @param bool $new
     *
     * @return string
     */
    public static function id($new = false)
    {
        if ($new && session_id()) {
            session_regenerate_id(true);
        }

        return session_id() ?: '';
    }

    /**
     * Destroy the session.
     */
    public static function destroy()
    {
        if (self::id()) {
            session_unset();
            session_destroy();
            session_write_close();

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 4200,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
        }

        return true;
    }

    /**
     * Creates a flash message that will be removed after a refresh
     */
    public function setFlash($type, $content)
    {
        return $this->set( array( 'flash' => array($type => $content) ) );
    }

    /**
     * Static Label of setFlash
     */
    public static function _setFlash($type, $content)
    {
        static::_set(array( 'flash' => array($type => $content) ));
    }

    /**
     * Retrives a session flash message
     */
    public function getFlash($key = 'flash')
    {
        $key !== "flash" and $key = "flash.".$key;

        return $this->get($key);
    }

    /**
     * Static Label of setFlash
     */
    public static function _getFlash($key = 'flash')
    {
        $key !== "flash" and $key = "flash.".$key;

        return static::_get($key);
    }

    /**
     * Cleans all flash values
     */
    public function clearFlash()
    {
        if ( ! $this->keepFlash )
        {
            if ( $this->get('flash') )
            {
                $this->delete('flash');
            }
        }
    }

    /**
     * Magic method for get.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Magic method for set.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Magic method for delete.
     *
     * @param string $key
     */
    public function __unset($key)
    {
        $this->delete($key);
    }

    /**
     * Magic method for exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return $this->exists($key);
    }

    /**
     * Count elements of an object.
     *
     * @return int
     */
    public function count()
    {
        return count($_SESSION);
    }

    /**
     * Retrieve an external Iterator.
     *
     * @return \Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($_SESSION);
    }

    /**
     * Whether an array offset exists.
     *
     * @param mixed $offset
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }

    /**
     * Retrieve value by offset.
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set a value by offset.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Remove a value by offset.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->delete($offset);
    }
}
