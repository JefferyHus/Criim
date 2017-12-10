<?php


/**
 * @package Criim
 * @version 1.0.0
 * @author Jaafari El Housseine <jefferytutorials@gmail.com>
 * @link http://github.com/jefferyhus
 * @copyright All rights reserved
 * @license proprietary
 */

namespace Criim\Core\Auth;

use Criim\Core\Session;
use Criim\Core\DB;
use Criim\Core\Input;
use Carbon\Carbon;

class SimpleAuth
{
	/**
	 * The current user
	 */
	protected $user;

	/**
	 * Value for guest login
	 */
	protected static $guest_login = array(
		'uuid' => 0,
		'username' => 'guest',
		'group' => '0',
		'login_hash' => false,
		'email' => false,
	);

	/**
	 * Session_Cookie  session object for the remember-me feature
	 */
	protected static $remember_me = null;

	/**
	 * Check for login
	 *
	 * @return  bool
	 */
	public function perform_check()
	{
		// fetch the username and login hash from the session
		$username    = Session::_get('username');
		$login_hash  = Session::_get('login_hash');

		// only worth checking if there's both a username and login-hash
		if ( ! empty($username) and ! empty($login_hash))
		{
			if (is_null($this->user) or ($this->user['username'] != $username and $this->user != static::$guest_login))
			{
				$this->user = DB::from(env('SIMPLE_AUTH_TABLE'))
					->where('username')->eq($username)
					->select(env('SIMPLE_AUTH_TABLE_COLUMNS', '*'))
					->fetchAssoc()
					->first();
			}

			// return true when login was verified, and either the hash matches or multiple logins are allowed
			if ($this->user and (env('SIMPLE_AUTH_MULTI_LOGIN', false) or $this->user['login_hash'] === $login_hash))
			{
				return true;
			}
		}
		// not logged in, do we have remember-me active and a stored user_id?
		elseif (static::$remember_me and $user_id = static::$remember_me->get('user_id', null))
		{
			return $this->force_login($user_id);
		}

		// no valid login when still here, ensure empty session and optionally set guest_login
		$this->user = env('SIMPLE_AUTH_GUEST', true) ? static::$guest_login : false;
		Session::_delete('username');
		Session::_delete('login_hash');

		return false;
	}

	/**
	 * Logout user
	 *
	 * @return  bool
	 */
	public function logout()
	{
		$this->user = env('SIMPLE_AUTH_GUEST', true) ? static::$guest_login : false;
		Session::_delete('username');
		Session::_delete('login_hash');
		return true;
	}

	/**
	 * Sign-in the user and creates a session in order for the logout to find us
	 */
	public function login($username_or_email = '', $password = '')
	{
		if ( ! ($this->user = $this->validate_user($username_or_email, $password)))
		{
			$this->user = env('SIMPLE_AUTH_GUEST', true) ? static::$guest_login : false;
			
			Session::_delete('username');
			Session::_delete('login_hash');
			return false;
		}

		Session::_set('username', $this->user['username']);
		Session::_set('login_hash', $this->create_login_hash());
		Session::id();
		
		return true;
	}

	/**
	 * Force login user
	 *
	 * @param   string
	 * @return  bool
	 */
	public function force_login($user_id = '')
	{
		if (empty($user_id))
		{
			return false;
		}

		$this->user = DB::from(env('SIMPLE_AUTH_TABLE'))
		->where('uuid')->eq($user_id)
		->select(env('SIMPLE_AUTH_TABLE_COLUMNS', '*'))
		->fetchAssoc()
		->first();

		if ($this->user == false)
		{
			$this->user = env('SIMPLE_AUTH_GUEST', true) ? static::$guest_login : false;

			Session::_delete('username');
			Session::_delete('login_hash');

			return false;
		}

		Session::_set('username', $this->user['username']);
		Session::_set('login_hash', $this->create_login_hash());

		// and rotate the session id, we've elevated rights
		Session::id();

		return true;
	}

	/**
	 * Validates the user if he exists or not
	 */
	public function validate_user($username_or_email = '', $password = '')
	{
		$username_or_email = trim($username_or_email) ?: trim(Input::post(env('SIMPLE_AUTH_USERNAME_KEY', 'username')));
		$password = trim($password) ?: trim(Input::post(env('SIMPLE_AUTH_PASSWORD_KEY', 'password')));

		if ( empty($username_or_email) or empty($password) )
		{
			return false;
		}

		$password = $this->hash_password($password);

		$user = DB::from(env('SIMPLE_AUTH_TABLE', 'users'))
		->where('username')->eq($username_or_email)
		->andWhere('password')->eq($password)
		->select()
		->fetchAssoc()
		->first();

		return $user ?: false;
	}

	/**
	 * Creates a temporary hash that will validate the current login
	 *
	 * @return  string
	 */
	public function create_login_hash()
	{
		if (empty($this->user))
		{
			throw new \Exception('User not logged in, can\'t create login hash.', 10);
		}

		$last_login = Carbon::now()->parse()->timestamp;

		$login_hash = sha1($this->user['username'].$last_login);

		DB::update(env('SIMPLE_AUTH_TABLE', 'users'))
		->where('username')->is($this->user['username'])
		->set(array('last_login' => $last_login, 'login_hash' => $login_hash));

		$this->user['login_hash'] = $login_hash;

		return $login_hash;
	}

	/**
	 * Hashes the password
	 */
	public function hash_password($password = '')
	{
		return base64_encode(hash_pbkdf2('sha256', $password, env('SIMPLE_AUTH_SALT'), env('SIMPLE_AUTH_ITERATIONS', 10000), 32, true));
	}


	/**
	 * Create new user
	 *
	 * @param   string
	 * @param   string
	 * @param   string  must contain valid email address
	 * @param   int     group id
	 * @param   Array
	 * @return  bool
	 */
	public function create_user($username, $password, $email, $group = 1, Array $profile_fields = array())
	{
		$password = trim($password);
		$email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);

		if (empty($username) or empty($password) or empty($email))
		{
			throw new \Exception('Username, password or email address is not given, or email address is invalid', 1);
		}

		$same_users = DB::from(env('SIMPLE_AUTH_TABLE'))
		->where('username')->eq($username)
		->orWhere('email')->eq($email)
		->select(env('SIMPLE_AUTH_TABLE_COLUMNS', '*'));

		if ($same_users->count() > 0)
		{
			if (in_array(strtolower($email), array_map('strtolower', $same_users->fetchAssoc()->first())))
			{
				throw new \Exception('Email address already exists', 2);
			}
			else
			{
				throw new \Exception('Username already exists', 3);
			}
		}

		$user = array(
			'uuid'            => DB::randomKey(),
			'username'        => (string) $username,
			'password'        => $this->hash_password((string) $password),
			'email'           => $email,
			'group'           => (int) $group,
			'profile_fields'  => serialize($profile_fields),
			'last_login'      => 0,
			'login_hash'      => ''
		);

		$result = DB::insert($user)
		->into(env('SIMPLE_AUTH_TABLE'));
		
		return ($result[1] > 0) ? $result[0] : false;
	}

	/**
	 * Update a user's properties
	 * Note: Username cannot be updated, to update password the old password must be passed as old_password
	 *
	 * @param   Array  properties to be updated including profile fields
	 * @param   string
	 * @return  bool
	 */
	public function update_user($values, $username = null)
	{
		$username = $username ?: $this->user['username'];

		$current_values = DB::from(env('SIMPLE_AUTH_TABLE'))
		->where('username')->eq($username)
		->select(env('SIMPLE_AUTH_TABLE_COLUMNS', '*'))
		->fetchAssoc()
		->first();

		if (empty($current_values))
		{
			throw new \Exception('Username not found', 4);
		}

		$update = array();

		if (array_key_exists('username', $values))
		{
			throw new \Exception('Username cannot be changed.', 5);
		}

		if (array_key_exists('password', $values))
		{
			if (empty($values['old_password'])
				or Arr::get($current_values, 'password') != $this->hash_password(trim($values['old_password'])))
			{
				throw new \Exception('Old password is invalid');
			}

			$password = trim(strval($values['password']));

			if ($password === '')
			{
				throw new \Exception('Password can\'t be empty.', 6);
			}

			$update['password'] = $this->hash_password($password);

			unset($values['password']);
		}

		if (array_key_exists('old_password', $values))
		{
			unset($values['old_password']);
		}

		if (array_key_exists('email', $values))
		{
			$email = filter_var(trim($values['email']), FILTER_VALIDATE_EMAIL);

			if ( ! $email)
			{
				throw new \Exception('Email address is not valid', 7);
			}

			$matches = DB::from(env('SIMPLE_AUTH_TABLE'))
			->where('email')->eq($email)
			->andWhere('uuid')->ne($current_values['uuid'])
			->select()
			->fetchAssoc()
			->all();

			if (count($matches))
			{
				throw new \Exception('Email address is already in use', 11);
			}

			$update['email'] = $email;

			unset($values['email']);
		}

		if (array_key_exists('group', $values))
		{
			if (is_numeric($values['group']))
			{
				$update['group'] = (int) $values['group'];
			}

			unset($values['group']);
		}

		if ( ! empty($values))
		{
			$profile_fields = @unserialize(Arr::get($current_values, 'profile_fields')) ?: array();

			foreach ($values as $key => $val)
			{
				if ($val === null)
				{
					unset($profile_fields[$key]);
				}
				else
				{
					$profile_fields[$key] = $val;
				}
			}

			$update['profile_fields'] = serialize($profile_fields);
		}

		$update['updated_at'] = Carbon::now()->parse()->timestamp;

		$affected_rows = DB::update(env('SIMPLE_AUTH_TABLE'))
		->set($update)
		->where('username')->eq($username);

		// Refresh user
		if ($this->user['username'] == $username)
		{
			$this->user = DB::from(env('SIMPLE_AUTH_TABLE'))
			->where('username')->eq($username)
			->select(env('SIMPLE_AUTH_TABLE_COLUMNS', '*'))
			->fetchAssoc()
			->first();
		}

		return $affected_rows > 0;
	}

	/**
	 * Getter for user data
	 *
	 * @param  string  name of the user field to return
	 * @param  mixed  value to return if the field requested does not exist
	 *
	 * @return  mixed
	 */
	public function get($field, $default = null)
	{
		if (isset($this->user[$field]))
		{
			return $this->user[$field];
		}
		elseif (isset($this->user['profile_fields']))
		{
			return $this->get_profile_fields($field, $default);
		}

		return $default;
	}

	/**
	 * Get the user's profile fields
	 *
	 * @return  Array
	 */
	public function get_profile_fields($field = null, $default = null)
	{
		if (empty($this->user))
		{
			return false;
		}

		if (isset($this->user['profile_fields']))
		{
			is_array($this->user['profile_fields']) or $this->user['profile_fields'] = (@unserialize($this->user['profile_fields']) ?: array());
		}
		else
		{
			$this->user['profile_fields'] = array();
		}

		return is_null($field) ? $this->user['profile_fields'] : Arr::get($this->user['profile_fields'], $field, $default);
	}
}