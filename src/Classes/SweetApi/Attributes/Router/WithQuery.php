<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class WithQuery extends WithParam 
{
    public function __construct(string $name = '', string $description = '', bool $required = false, bool $deprecated = false, bool $allowEmptyValue = false, string $example = '')
    {
        parent::__construct($name, 'query', $description, $required, $deprecated, $allowEmptyValue, $example);
    }
}