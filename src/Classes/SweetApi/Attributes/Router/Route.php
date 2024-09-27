<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

use InvalidArgumentException;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Route 
{
    public function __construct(public string $path = '', public string $verbs = '*', public string $name = '', public array $consumes = [], public string $summary = '', public string $description = '', public bool $deprecated = false)
    {
        $verbs = explode('|', $this->verbs);

        foreach($verbs as $verb) {
            if (!in_array(strtolower($verb), ['*', 'get', 'post', 'put', 'patch', 'delete', 'options', 'head', 'trace'])) {
                throw new InvalidArgumentException('Invalid verb "' . $verb . '"');
            }
        }
    }
}
