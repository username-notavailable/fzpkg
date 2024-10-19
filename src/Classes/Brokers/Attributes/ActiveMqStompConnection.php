<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Brokers\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ActiveMqStompConnection extends BrokerStompConnection 
{
    public function __construct(string $connectionName)
    {
        parent::__construct('activemq', 'stomp', $connectionName);
    }
}
