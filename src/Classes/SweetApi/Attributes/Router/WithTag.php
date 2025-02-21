<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

use InvalidArgumentException;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class WithTag 
{
    public function __construct(public array $schemaParams = [])
    {
        if (!array_key_exists('name', $this->schemaParams)) {
            throw new InvalidArgumentException(__METHOD__ .': Param "name" is required');
        }
    }
}
