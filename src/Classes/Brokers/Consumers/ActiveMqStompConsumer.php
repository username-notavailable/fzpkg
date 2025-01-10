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

        if ($connectionData['useHeartbeat'] && ($connectionData['client']['heartbeatSend'] > 0 || $connectionData['client']['heartbeatReceive'] > 0)) {
            $client->setHeartbeat($connectionData['client']['heartbeatSend'], $connectionData['client']['heartbeatReceive']);

            $emitter = new HeartbeatEmitter($client->getConnection(), $connectionData['client']['heartbeatIntervalUsage']);
            $client->getConnection()->getObservers()->addObserver($emitter);
        }

        // ---

        if ($connectionData['client']['receiptWait'] > 0) {
            $client->setReceiptWait($connectionData['client']['receiptWait']);
        }

        if (is_null($connectionData['client']['clientId'])) {
            $connectionData['client']['clientId'] = uniqid($microserviceName . '.activemq.stomp');
        }

        $client->setClientId($connectionData['client']['clientId']);
        
        if ($connectionData['checkServerAlive'] && $connectionData['client']['serverAliveIntervalUsage'] > 0) {
            $observer = new ServerAliveObserver($connectionData['client']['serverAliveIntervalUsage']);
            $client->getConnection()->getObservers()->addObserver($observer);
        }

        $client->setSync($connectionData['client']['sync']);
    }

    public function run() : void
    {

    }
}