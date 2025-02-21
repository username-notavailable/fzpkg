<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

use InvalidArgumentException;

#[\Attribute(\Attribute::TARGET_METHOD)]
class RequestBody
{
    public function __construct(public array $schemaParams = [])
    {
        if (!array_key_exists('content', $this->schemaParams)) {
            throw new InvalidArgumentException(__METHOD__ .': Param "content" is required');
        }
    }
}