<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Brokers\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS)]
class BrokerConnection 
{
    public function __construct(public string $broker, public string $protocol, public string $connectionName)
    {}
}
