<?php

$config = [
    'settings' => [
        // Only set this if you need access to route within middleware
        'determineRouteBeforeAppMiddleware' => true,
        'displayErrorDetails' => true,
        'logger' => [
            'name' => 'gronpark-app',
            'level' => Monolog\Logger::DEBUG,
            'path' => __DIR__ . '/app/logs/app.log',
        ],
    ],
];

use Criim\App\Http\Controllers;
use Criim\App\Http\Middleware;

// create a new application

$route = new Slim\App($config);

// return the routes
return $route;