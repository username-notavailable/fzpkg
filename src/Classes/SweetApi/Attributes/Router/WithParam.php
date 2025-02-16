<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

use InvalidArgumentException;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class WithParam 
{
    public function __construct(public string $name = '', public string $in = '', public string $description = '', public bool $required = false, public bool $deprecated = false, public bool $allowEmptyValue = false, public string $example = '')
    {
        if (!in_array(strtolower($in), ['query', 'header', 'path', 'cookie'])) {
            throw new InvalidArgumentException('Invalid in value "' . $in . '"');
        }
    }
}