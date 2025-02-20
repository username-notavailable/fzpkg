<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Connect extends Route
{
    public function __construct(string $path = '', string $name = '', array $where = [], array $whereNumber = [], array $whereAlpha = [], array $whereAlphaNumeric = [], array $whereUuid = [], array $whereUlid = [], array $whereIn = [], bool $withTrashed = false, ?bool $scopeBindings = null, array $schemaParams = [])
    {
        parent::__construct($path, 'CONNECT', $name, $where, $whereNumber, $whereAlpha, $whereAlphaNumeric, $whereUuid, $whereUlid, $whereIn, $withTrashed, $scopeBindings, $schemaParams);
    }
}
