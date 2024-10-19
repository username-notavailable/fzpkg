<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Brokers;

use Fuzzy\Fzpkg\Classes\Brokers\Consumers\MicroserviceConsumer;
use Fuzzy\Fzpkg\Classes\Brokers\Consumers\ActiveMqStompConsumer;
use Exception;

class Microservice 
{
    public static function createConsumerInstance(string $microserviceName, string $broker, string $protocol, array $connectionData, array $mappedActions) : MicroserviceConsumer
    {
        //### FIXME: Aggiungere la roba che manca
        if ($protocol === 'stomp') {
            if (!\Composer\InstalledVersions::isInstalled('stomp-php/stomp-php')) {
                throw new Exception('STOMP protocol require "stomp-php/stomp-php" package');
            }
            else {
                switch ($broker)
                {
                    case 'activemq':
                        return new ActiveMqStompConsumer($microserviceName, $connectionData, $mappedActions);
                        break;

                    default:
                        throw new Exception($broker . '/' . $protocol . ' not supported');
                        break;
                }
            }
        }
    }
}