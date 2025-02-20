<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_METHOD)]
class WithBody extends WithParam 
{
    public function __construct(array $schemaParams = [])
    {
        $schemaParams['in'] = 'body';
        
        parent::__construct($schemaParams);
    }
}