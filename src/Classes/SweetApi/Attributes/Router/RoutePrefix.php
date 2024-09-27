<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_CLASS)]
class RoutePrefix 
{
    public function __construct(public string $path = '')
    {}
}
