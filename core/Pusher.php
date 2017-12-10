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

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

class Pusher extends Socket implements WampServerInterface
{
	/**
	 * All subscribed topics
	 */
	protected $subscribedTopics = [];

    /**
     * An RPC call has been received
     * 
     * @param \Ratchet\ConnectionInterface $conn
     * @param string                       $id The unique ID of the RPC, required to respond to
     * @param string|Topic                 $topic The topic to execute the call against
     * @param array                        $params Call parameters received from the client
     */
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
    	
    }

    /**
     * The data that has been subscribed by the client
     * @param  String $entry a JSON string sent by the ZeroMQ
     * @return void
     */
    public function onDataEntry($entry)
    {
    	echo sprintf("Data recieved %s", $entry);
    }

    /**
     * A request to subscribe to a topic has been made
     * 
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic                 $topic The topic to subscribe to
     */
    public function onSubscribe(ConnectionInterface $conn, $topic)
    {
    	$this->subscribedTopics[$topic->getId()] = $topic;
    }

    /**
     * A request to unsubscribe from a topic has been made
     * 
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic                 $topic The topic to unsubscribe from
     */
    public function onUnSubscribe(ConnectionInterface $conn, $topic)
    {

    }

    /**
     * A client is attempting to publish content to a subscribed connections on a URI
     * 
     * @param \Ratchet\ConnectionInterface $conn
     * @param string|Topic                 $topic The topic the user has attempted to publish to
     * @param string                       $event Payload of the publish
     * @param array                        $exclude A list of session IDs the message should be excluded from (blacklist)
     * @param array                        $eligible A list of session Ids the message should be send to (whitelist)
     */
    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {

    }
}