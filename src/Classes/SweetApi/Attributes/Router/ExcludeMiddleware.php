<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ExcludeMiddleware extends BaseMiddleware
{
    public function __construct(public string $class = '', public string $alias = '', public string $group = '')
    {
        parent::__construct($class, $alias, $group, true);
    }
}
