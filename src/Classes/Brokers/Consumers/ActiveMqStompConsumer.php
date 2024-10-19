<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Brokers\Consumers;

use Stomp\Client;
// use Stomp\Exception\ConnectionException;
use Stomp\Network\Connection;
use Stomp\Network\Observer\HeartbeatEmitter;
use Stomp\Network\Observer\ServerAliveObserver;
// use Stomp\StatefulStomp;
// use Stomp\Transport\Frame;
// use Stomp\Transport\Message;

class ActiveMqStompConsumer extends MicroserviceConsumer
{
    private $client;
    private $mappedActions;

    public function __construct(string $microserviceName, array $connectionData, array $mappedActions)
    {
        $this->mappedActions = $mappedActions;

        parent::__construct($microserviceName, 'activemq', 'stomp');

        $connection = new Connection($connectionData['brokerUri'], $connectionData['connectionTimeout'], $connectionData['context']);
        $connection->setReadTimeout($connectionData['readTimeoutSeconds'], $connectionData['readTimeoutMicroseconds']);
        $connection->setWriteTimeout($connectionData['writeTimeout']);
        $connection->setMaxReadBytes($connectionData['maxReadBytes']);
        $connection->setMaxWriteBytes($connectionData['maxWriteBytes']);
        $connection->setPersistentConnection($connectionData['persistentConnection']);

        $client = new Client($connection);

        if (!empty($connectionData['client']['versions'])) {
            $client->setVersions($connectionData['client']['versions']);
        }

        if (!is_null($connectionData['client']['login']) && !is_null($connectionData['client']['passcode'])) {
            $client->setLogin($connectionData['client']['login'], $connectionData['client']['passcode']);
        }

        if (!is_null($connectionData['client']['host'])) {
            $client->setVhostname($connectionData['client']['host']);
        }

        if ($connectionData['client']['heartbeatSend'] > 0 || $connectionData['client']['heartbeatReceive'] > 0) {
            $client->setHeartbeat($connectionData['client']['heartbeatSend'], $connectionData['client']['heartbeatReceive']);

            $emitter = new HeartbeatEmitter($client->getConnection(), $connectionData['heartbeatIntervalUsage']);
            $client->getConnection()->getObservers()->addObserver($emitter);
        }

        // ---

        if (!is_null($connectionData['client']['host'])) {
            $client->setVhostname($connectionData['client']['host']);
        }

        if (!is_null($connectionData['client']['host'])) {
            $client->setVhostname($connectionData['client']['host']);
        }
        
        
        if ($connectionData['useHeartbeat']) {

        }
        
        if ($connectionData['checkServerAlive']) {
            // in order to simplify the process of checking for server signals we use the ServerAliveObserver
            $observer = new ServerAliveObserver();
            $client->getConnection()->getObservers()->addObserver($observer);
        }
    }

    public function run() : void
    {

    }
}