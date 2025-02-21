<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

use InvalidArgumentException;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class WithRequestBody 
{
    public function __construct(public string $name, public array $schemaParams)
    {
        if (!array_key_exists('content', $this->schemaParams)) {
            throw new InvalidArgumentException(__METHOD__ .': Param "content" is required');
        }
    }
}