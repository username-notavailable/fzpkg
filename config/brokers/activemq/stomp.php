<?php

return [

    /*
    |--------------------------------------------------------------------------
    | STOMP Connections
    |--------------------------------------------------------------------------
    |
    |
    |
    */

    'stompConnections' => [

        'example_connection' => [
            'brokerUri' => 'failover://(tcp://localhost:61614,ssl://localhost:61612,tcp://localhost:61613)?randomize=false', 
            'connectionTimeout' => 1, 
            'context' => [],
            'useHeartbeat' => true,
            'checkServerAlive' => true,
            'readTimeoutSeconds' => 60,
            'readTimeoutMicroseconds' => 0,
            'writeTimeout' => 3,
            'maxReadBytes' => 8192,
            'maxWriteBytes' => 8192,
            'persistentConnection' => false,
            'topics' => [
                [
                    'name' => 'topic1', 
                    'destination' => '/topic/topic1',
                    'durable' => true,
                    'selector' => null,
                    'ack' => 'auto',
                    'subscriptionId' => null,
                    'autoActivate' => true
                ]
            ],
            'queues' => [
                [
                    'name' => 'queue1', 
                    'destination' => '/queue/queue1',
                    'durable' => true,
                    'selector' => null,
                    'ack' => 'auto',
                    'subscriptionId' => null,
                    'autoActivate' => true
                ]
            ],
            'client' => [
                'sync' => true,
                'clientId' => null,
                'sessionId' => null,
                'receiptWait' => 2,
                'login' => null,
                'passcode' => null,
                'versions' => ['1.0', '1.1', '1.2'],
                'host' => null,
                'heartbeatSend' => 0,
                'heartbeatReceive' => 0,
            ]
        ]

    ]

];
