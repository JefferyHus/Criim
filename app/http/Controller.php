<?php
/**
 * @package Criim
 * @version 1.0.0
 * @author Jaafari El Housseine <jefferytutorials@gmail.com>
 * @link http://github.com/jefferyhus
 * @copyright All rights reserved
 * @license proprietary
 */

namespace Criim\App\Http;

use Interop\Container\ContainerInterface;

class Controller
{
	/**
	* Container object
	*/
	protected $container;

	// constructor receives container instance
	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}

	public function getRouter()
	{
		return $this->container->get('router');
	}
}