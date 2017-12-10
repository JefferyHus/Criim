<?php

/**
 * @package Abacus/Security
 * @version 1.0.0
 * @author Jaafari El Housseine <jefferytutorials@gmail.com>
 * @link http://github.com/jefferyhus
 * @copyright All rights reserved
 * @license proprietary
 */

namespace Criim\App\Http\Middleware;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Exception\NotFoundException;
use Criim\Core\Middleware;
use Criim\Core\Auth;
use Criim\Core\Session;
use Criim\Core\Input;

class AuthMiddleware
{
    /**
     * The container object
     */
    protected $container;

    /**
     * Auth middleware invokable class
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
	public function __invoke(Request $request, Response $response, callable $next)
	{
		$redirect = null;
        $route    = $request->getAttribute('route');
        $router   = $this->container->get('router');
        
        // return NotFound for non existent route
        if (empty($route))
        {
            throw new NotFoundException($request, $response);
        }
        
        // check if there is a logged in user
        if ( ! Auth::check() )
        {
            if ( $route->getName() !== "auth.home" )
            {
                $redirect = $router->pathFor('auth.home', [], ['back' => $route->getName()]);
            }
        }
        
        // if the redirect is not empty then redirect
        if ( !empty($redirect) )
        {
            return $response->withStatus(302)->withHeader('Location', $redirect);
        }

		$response = $next($request, $response);

		return $response;
	}

    /**
     * Init router
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
}