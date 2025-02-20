<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ResponseHtmx extends Response
{
    public function __construct(int $statusCode = 200, array $schemaParams = [])
    {
        //$schemaParams['content'] = 'text/html';

        parent::__construct($statusCode, $schemaParams);
    }
}
