<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Delete extends Route
{
    public function __construct(string $path = '', string $name = '', array $consumes = [], string $summary = '', string $description = '', bool $deprecated = false)
    {
        parent::__construct($path, 'DELETE', $name, $consumes, $summary, $description, $deprecated);
    }
}
