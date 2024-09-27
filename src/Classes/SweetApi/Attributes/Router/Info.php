<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Info 
{
    public function __construct(public string $description = '', public string $link = '', public string $linkDescription = '')
    {}
}
