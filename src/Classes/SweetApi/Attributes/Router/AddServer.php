<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

use InvalidArgumentException;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class AddServer 
{
    public function __construct(public array $schemaParams)
    {
        foreach (['url'] as $paramName) {
            if (!array_key_exists($paramName, $this->schemaParams)) {
                throw new InvalidArgumentException(__METHOD__ .': Param "' . $paramName . '" is required');
            }
        }
    }
}