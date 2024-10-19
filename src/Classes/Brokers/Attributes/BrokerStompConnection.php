<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Brokers\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class BrokerStompConnection extends BrokerConnection 
{
    public function __construct(string $broker, string $connectionName)
    {
        parent::__construct($broker, 'stomp', $connectionName);
    }
}
