<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\Brokers\Attributes;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Topic 
{
    public function __construct(public string $name)
    {}
}
