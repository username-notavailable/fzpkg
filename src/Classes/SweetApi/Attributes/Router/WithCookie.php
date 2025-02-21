<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class WithCookie extends WithParam 
{
    public function __construct(string $name, array $schemaParams)
    {
        $schemaParams['in'] = 'cookie';
        
        parent::__construct($name, $schemaParams);
    }
}