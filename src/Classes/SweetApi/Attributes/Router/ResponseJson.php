<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ResponseJson extends Response
{
    public function __construct(int $statusCode = 200, array $schemaParams = [])
    {
        //$schemaParams['content'] = 'application/json';

        parent::__construct($statusCode, $schemaParams);
    }
}
