<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class WithQuery extends WithParam 
{
    public function __construct(string $name, array $schemaParams)
    {
        $schemaParams['in'] = 'query';
        
        parent::__construct($name, $schemaParams);
    }
}