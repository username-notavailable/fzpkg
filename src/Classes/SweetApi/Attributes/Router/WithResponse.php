<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class WithResponse 
{
    public function __construct(public string $name, public array $schemaParams)
    {
        if (!array_key_exists('description', $this->schemaParams)) {
            $this->schemaParams['description'] = 'HTTP Response';
        }
    }
}