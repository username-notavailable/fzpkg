<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_METHOD)]
class RequestJson extends RequestBody
{
    public function __construct(array $schemaParams = [])
    {
        $schemaParams['content'] = 'application/json';

        parent::__construct($schemaParams);
    }
}
