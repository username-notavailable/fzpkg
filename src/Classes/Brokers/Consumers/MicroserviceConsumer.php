<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Brokers\Consumers;

abstract class MicroserviceConsumer 
{
    private $microserviceName;
    private $broker;
    private $protocol;

    public function __construct(string $microserviceName, string $broker, string $protocol)
    {
        $this->microserviceName = $microserviceName;
        $this->broker = $broker;
        $this->protocol = $protocol;
    }

    public function microserviceName() : string
    {
        return $this->microserviceName;
    }

    public function brokerType() : string
    {
        return $this->broker;
    }

    public function protocolType() : string
    {
        return $this->protocol;
    }

    abstract public function run() : void;
}