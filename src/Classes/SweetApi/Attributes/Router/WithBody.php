<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

use InvalidArgumentException;

#[\Attribute(\Attribute::TARGET_METHOD)]
class WithBody
{
    public string $in;

    public function __construct(public string $name = '', public string $description = '', public bool $required = false, public bool $deprecated = false, public bool $allowEmptyValue = false, public string $example = '')
    {
        $this->in = 'body';
    }
}