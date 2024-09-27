<?php

declare(strict_types=1);

namespace Fuzzy\Fzpkg\Classes\SweetApi\Attributes\Router;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class BaseMiddleware
{
    public function __construct(public string $class = '', public string $alias = '', public string $group = '', public bool $exclude = false)
    {}

    public function getName() : string
    {
        $name = '';

        if ($this->class !== '') {
            $name = $this->class;
        }
        else if ($this->alias !== '') {
            $name = $this->alias;
        }
        else if ($this->group !== '') {
            $name = $this->group;
        }

        return "'" . $name . "'";
    }
}
