<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Response 
{
    public function __construct(public string $statusCode, public string $content = 'string', public string $description = '')
    {}
}
