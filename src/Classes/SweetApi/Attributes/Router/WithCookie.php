<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class WithCookie extends WithParam 
{
    public function __construct(array $schemaParams = [])
    {
        $schemaParams['in'] = 'cookie';
        
        parent::__construct($schemaParams);
    }
}