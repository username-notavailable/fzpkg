<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RouteResponse 
{
    public function __construct(public int $statusCode = 200, public array $schemaParams)
    {
        if (!array_key_exists('$ref', $this->schemaParams)) {
            if (!array_key_exists('description', $this->schemaParams)) {
                $this->schemaParams['description'] = 'HTTP Response';
            }
        }
    }
}
