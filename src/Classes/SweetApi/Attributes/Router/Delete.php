<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Delete extends Route
{
    public function __construct(string $path = '', string $name = '', array $where = [], array $whereNumber = [], array $whereAlpha = [], array $whereAlphaNumeric = [], array $whereUuid = [], array $whereUlid = [], array $whereIn = [], bool $withTrashed = false, ?bool $scopeBindings = null, array $consumes = [], string $summary = '', string $description = '', bool $deprecated = false)
    {
        parent::__construct($path, 'DELETE', $name, $where, $whereNumber, $whereAlpha, $whereAlphaNumeric, $whereUuid, $whereUlid, $whereIn, $withTrashed, $scopeBindings, $consumes, $summary, $description, $deprecated);
    }
}
