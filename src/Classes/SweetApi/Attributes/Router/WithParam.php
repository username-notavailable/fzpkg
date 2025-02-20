<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

use InvalidArgumentException;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class WithParam 
{
    public function __construct(public array $schemaParams = [])
    {
        foreach (['name', 'in'] as $paramName) {
            if (!array_key_exists($paramName, $this->schemaParams)) {
                throw new InvalidArgumentException(__METHOD__ .': Param "' . $paramName . '" is required');
            }
        }
        
        if (!in_array($this->schemaParams['in'], ['query', 'header', 'path', 'cookie'])) {
            throw new InvalidArgumentException(__METHOD__ . ': Invalid "in" value (' . $this->schemaParams['in'] . ')');
        }
    }
}