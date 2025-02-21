<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RouteQuery extends RouteParam 
{
    public function __construct(array $schemaParams)
    {
        if (!array_key_exists('$ref', $schemaParams)) {
            $schemaParams['in'] = 'query';
        }
        
        parent::__construct($schemaParams);
    }
}
