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

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Socket implements MessageComponentInterface
{
	/**
	 * Connected clients
	 */
	protected $clients;

	/**
	 * Classes to call, or function
	 */
	protected $actions = [];

	/**
	 * Init the clients object
	 */
    public function __construct($actions = [])
    {
    	/**
    	 * [$this->actions the actions to take in accordance with the recieved message]
    	 * @var [array]
    	 */
    	$this->actions = array_merge($this->actions, $actions);

        $this->clients = new \SplObjectStorage;
    }

    /**
     * When a new connection is opened it will be passed to this method
     * 
     * @param  ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);
        // show that the connection has been established.
        // echo "Connected\n";
    }

    /**
     * Triggered when a client sends data through the socket
     * 
     * @param  \Ratchet\ConnectionInterface $from The socket/connection that sent the message to your application
     * @param  string                       $msg  The message received
     * @throws \Exception
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
    	// decode the object and find if we have an instance of that class
    	foreach ($this->clients as $key => $client)
    	{
    		if ( $from !== $client )
    		{
    			$client->send($msg);
    		}
    	}
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it 
     * has already been closed.
     * 
     * @param  ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);
        // echo "Connection {$conn->resourceId} has disconnected\n";
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * 
     * @param  ConnectionInterface $conn
     * @param  \Exception          $e
     * @throws \Exception
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
    	echo sprintf("Error occured with message: %s", $e->getMessage());

    	$conn->close();
    }
}